<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\GoogleDriveClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Walks the Drive setup one link at a time, so a misconfiguration is reported
 * where it happens instead of surfacing as an opaque 404 three layers down.
 */
class GoogleDriveCheck extends Command
{
    protected $signature = 'drive:check {--write : Also create and delete a throwaway folder}';

    protected $description = 'Verify the Google Drive configuration end to end';

    public function handle(GoogleDriveClient $client): int
    {
        $root = (string) config('documents.google.root_folder_id');

        $this->line('driver       '.config('documents.driver'));

        $clientId = (string) config('documents.google.client_id');
        $clientSecret = (string) config('documents.google.client_secret');
        $refreshToken = (string) config('documents.google.refresh_token');

        // The client id and secret are checked before the refresh token, and by
        // shape as well as presence: a missing refresh token is the expected
        // state before `drive:authorize`, whereas a wrong client id would make
        // that command fail too. Pasting service-account fields into these
        // slots is an easy mistake — both JSONs speak of a "client id", but
        // only an OAuth client works on a personal account.
        if ($clientId === '') {
            $this->error('client id    MISSING  GOOGLE_DRIVE_CLIENT_ID');

            return self::FAILURE;
        }

        if (! str_ends_with($clientId, '.apps.googleusercontent.com')) {
            $this->error('client id    WRONG    expected an OAuth client id ending .apps.googleusercontent.com');
            $this->line('             a bare numeric id is the SERVICE ACCOUNT id from a key JSON');
            $this->line('             create an OAuth client (type: Desktop app) in Google Cloud Console');

            return self::FAILURE;
        }

        if ($clientSecret === '') {
            $this->error('secret       MISSING  GOOGLE_DRIVE_CLIENT_SECRET');

            return self::FAILURE;
        }

        if (str_starts_with($clientSecret, '-----BEGIN')) {
            $this->error('secret       WRONG    that is a PEM private key from a service-account JSON');
            $this->line('             GOOGLE_DRIVE_CLIENT_SECRET is the OAuth client secret (GOCSPX-...)');

            return self::FAILURE;
        }

        $this->info('client       ok       OAuth client id and secret look right');

        if ($refreshToken === '') {
            $this->error('refresh tok  MISSING  GOOGLE_DRIVE_REFRESH_TOKEN');
            $this->line('             run `php artisan drive:authorize`');

            return self::FAILURE;
        }

        $this->info('refresh tok  ok       present');

        if ($root === '') {
            $this->error('root folder  MISSING  GOOGLE_DRIVE_ROOT_FOLDER_ID');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Za-z0-9_-]{15,}$/', $root)) {
            $this->warn("root folder  SUSPECT  [{$root}] does not look like a Drive id");
            $this->line('             use the id from drive.google.com/drive/folders/<ID>');
        }

        try {
            // Any lookup forces the token refresh and proves the root resolves.
            $client->findFolder('__drive_check__', $root);
        } catch (Throwable $e) {
            $this->error('token/root   FAILED');
            $this->line('             '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('token        ok       refresh token exchanged');
        $this->info("root folder  ok       [{$root}] is reachable");

        if (! $this->option('write')) {
            $this->line('test write   skipped  pass --write to prove quota and permissions');

            return self::SUCCESS;
        }

        try {
            $id = $client->createFolder('__drive_check__', $root);
            $client->deleteFile($id);
        } catch (Throwable $e) {
            $this->error('test write   FAILED');
            $this->line('             '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('test write   ok       folder created and removed');

        return self::SUCCESS;
    }
}
