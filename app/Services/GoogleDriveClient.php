<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Drive REST v3 over Laravel's HTTP client.
 *
 * The official google/apiclient is not usable here: its v2 line requires
 * guzzlehttp/guzzle ^7.4.5 and Laravel 13 locks Guzzle to 8.x, so Composer
 * resolves it down to the abandoned v1.1.9. Talking to the REST API directly
 * costs no dependency and makes the whole thing fakeable with Http::fake().
 *
 * Auth is an OAuth refresh token for a real account, not a service account:
 * service accounts have no Drive storage quota, so on a personal Gmail account
 * every upload would fail with storageQuotaExceeded.
 */
class GoogleDriveClient
{
    private const FOLDER_MIME = 'application/vnd.google-apps.folder';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const FILES_URL = 'https://www.googleapis.com/drive/v3/files';

    private const UPLOAD_URL = 'https://www.googleapis.com/upload/drive/v3/files';

    /** Shared Drives are only visible to the API when these are set. */
    private const DRIVE_SCOPE = ['supportsAllDrives' => 'true', 'includeItemsFromAllDrives' => 'true'];

    public function findOrCreateFolder(string $name, string $parentId): string
    {
        return $this->findFolder($name, $parentId) ?? $this->createFolder($name, $parentId);
    }

    public function findFolder(string $name, string $parentId): ?string
    {
        $query = sprintf(
            "name = '%s' and mimeType = '%s' and '%s' in parents and trashed = false",
            $this->escape($name),
            self::FOLDER_MIME,
            $this->escape($parentId),
        );

        $response = $this->request()->get(self::FILES_URL, self::DRIVE_SCOPE + [
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        $this->assertOk($response, "searching for folder [{$name}]");

        return $response->json('files.0.id');
    }

    public function createFolder(string $name, string $parentId): string
    {
        $response = $this->request()->post(
            self::FILES_URL.'?'.http_build_query(self::DRIVE_SCOPE + ['fields' => 'id']),
            ['name' => $name, 'mimeType' => self::FOLDER_MIME, 'parents' => [$parentId]],
        );

        $this->assertOk($response, "creating folder [{$name}]");

        return (string) $response->json('id');
    }

    public function upload(UploadedFile $file, string $name, string $parentId): string
    {
        $response = $this->request()
            ->attach('metadata', json_encode([
                'name' => $name,
                'parents' => [$parentId],
            ], JSON_THROW_ON_ERROR), 'metadata.json', ['Content-Type' => 'application/json'])
            ->attach('file', $file->get(), $name, ['Content-Type' => $file->getClientMimeType()])
            ->post(self::UPLOAD_URL.'?'.http_build_query(
                self::DRIVE_SCOPE + ['uploadType' => 'multipart', 'fields' => 'id']
            ));

        $this->assertOk($response, "uploading [{$name}]");

        return (string) $response->json('id');
    }

    public function download(string $fileId): string
    {
        $response = $this->request()->get(
            self::FILES_URL.'/'.$fileId,
            self::DRIVE_SCOPE + ['alt' => 'media'],
        );

        $this->assertOk($response, "downloading [{$fileId}]");

        return $response->body();
    }

    public function fileExists(string $fileId): bool
    {
        return $this->request()
            ->get(self::FILES_URL.'/'.$fileId, self::DRIVE_SCOPE + ['fields' => 'id'])
            ->successful();
    }

    public function deleteFile(string $fileId): void
    {
        $response = $this->request()->delete(
            self::FILES_URL.'/'.$fileId.'?'.http_build_query(self::DRIVE_SCOPE)
        );

        // A file already gone is not an error worth failing a delete over.
        if ($response->status() !== 404) {
            $this->assertOk($response, "deleting [{$fileId}]");
        }
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->timeout((int) config('documents.google.timeout', 30))
            ->acceptJson();
    }

    private function accessToken(): string
    {
        $clientId = $this->credential('client_id');
        $clientSecret = $this->credential('client_secret');
        $refreshToken = $this->credential('refresh_token');

        // Keyed on the credentials themselves: a token cached under an older
        // client or refresh token must never be reused after they change.
        $key = 'google-drive.token.'.sha1($clientId.'|'.$refreshToken);

        return Cache::remember($key, now()->addMinutes(55), function () use ($clientId, $clientSecret, $refreshToken): string {
            $response = Http::asForm()
                ->timeout((int) config('documents.google.timeout', 30))
                ->post(self::TOKEN_URL, [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'refresh_token' => $refreshToken,
                    'grant_type' => 'refresh_token',
                ]);

            $this->assertOk($response, 'refreshing the access token');

            return (string) $response->json('access_token');
        });
    }

    /** Read one OAuth setting, failing with the env key rather than a later 4xx. */
    private function credential(string $key): string
    {
        $value = (string) config("documents.google.{$key}");

        if ($value === '') {
            throw new RuntimeException(
                'Google Drive: GOOGLE_DRIVE_'.strtoupper($key).' is not set. '
                .'Run `php artisan drive:authorize` to obtain a refresh token.'
            );
        }

        return $value;
    }

    private function assertOk(Response $response, string $what): void
    {
        if ($response->successful()) {
            return;
        }

        // Google answers an unresolvable parent with "File not found: ." and
        // location=fileId, which says nothing useful. Name the likely cause.
        if ($response->status() === 404) {
            throw new RuntimeException(sprintf(
                'Google Drive: %s failed — the parent folder was not found. GOOGLE_DRIVE_ROOT_FOLDER_ID '
                .'is currently [%s]; it must be the id from the folder URL '
                .'(drive.google.com/drive/folders/<ID>), not the folder name, and the folder must be '
                .'visible to the authorised account. (HTTP 404) %s',
                $what,
                (string) config('documents.google.root_folder_id'),
                $response->body(),
            ));
        }

        throw new RuntimeException(
            sprintf('Google Drive: %s failed (HTTP %d) %s', $what, $response->status(), $response->body())
        );
    }

    /** Single quotes terminate a Drive `q` string; company names contain them. */
    private function escape(string $value): string
    {
        return str_replace(['\\', "'"], ['\\\\', "\\'"], $value);
    }
}
