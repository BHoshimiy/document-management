<?php

declare(strict_types=1);

namespace App\Services\Storage;

use App\Contracts\DocumentStorage;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\GoogleDriveClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GoogleDriveStorage implements DocumentStorage
{
    public function __construct(private readonly GoogleDriveClient $client) {}

    public function store(Company $company, DocumentFolder $folder, UploadedFile $file): array
    {
        $companyFolderId = $this->client->findOrCreateFolder($company->name, $this->rootFolderId());
        $targetFolderId = $this->client->findOrCreateFolder($this->folderName($folder), $companyFolderId);

        return [
            'path' => null,
            'drive_file_id' => $this->client->upload($file, $file->getClientOriginalName(), $targetFolderId),
        ];
    }

    public function stream(Document $document): StreamedResponse
    {
        // Proxied through the app on purpose: a Drive link would bypass
        // DocumentPolicy and leak documents across companies.
        $contents = $this->client->download((string) $document->drive_file_id);

        return Response::streamDownload(
            fn () => print ($contents),
            $document->name,
            ['Content-Type' => $document->mime_type ?: 'application/octet-stream'],
        );
    }

    public function delete(Document $document): void
    {
        $this->client->deleteFile((string) $document->drive_file_id);
    }

    public function exists(Document $document): bool
    {
        return $this->client->fileExists((string) $document->drive_file_id);
    }

    /** @param  array{path: ?string, drive_file_id: ?string}  $stored */
    public function discard(array $stored): void
    {
        if (! empty($stored['drive_file_id'])) {
            $this->client->deleteFile($stored['drive_file_id']);
        }
    }

    /**
     * The English name, deliberately: `name` is translatable, so using the
     * current locale would create a second folder the first time someone
     * uploads in another language.
     */
    private function folderName(DocumentFolder $folder): string
    {
        return $folder->getTranslations('name')['en'] ?? $folder->name;
    }

    private function rootFolderId(): string
    {
        $root = (string) config('documents.google.root_folder_id');

        if ($root === '') {
            throw new RuntimeException('Google Drive: GOOGLE_DRIVE_ROOT_FOLDER_ID is not set.');
        }

        return $root;
    }
}
