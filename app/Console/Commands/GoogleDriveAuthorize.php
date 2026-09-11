<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-off consent that mints the refresh token the Drive client runs on.
 *
 * Google removed the out-of-band (urn:ietf:wg:oauth:2.0:oob) flow in 2022, so
 * this uses a loopback redirect. Create the OAuth client in Google Cloud
 * Console as application type "Desktop app", which permits http://localhost.
 */
class GoogleDriveAuthorize extends Command
{
    protected $signature = 'drive:authorize';

    protected $description = 'Obtain a Google Drive refresh token through a one-off consent';

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REDIRECT = 'http://localhost';

    public function handle(): int
    {
        $clientId = (string) config('documents.google.client_id');
        $clientSecret = (string) config('documents.google.client_secret');

        if ($clientId === '' || $clientSecret === '') {
            $this->error('Set GOOGLE_DRIVE_CLIENT_ID and GOOGLE_DRIVE_CLIENT_SECRET in .env first.');

            return self::FAILURE;
        }

        $url = self::AUTH_URL.'?'.http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive',
            // Both are required: without them Google returns no refresh token
            // on a repeat authorisation.
            'access_type' => 'offline',
            'prompt' => 'consent',
        ]);

        $this->line('1. Open this URL and approve access:');
        $this->newLine();
        $this->line($url);
        $this->newLine();
        $this->line('2. You will land on http://localhost/?code=... and the browser will show');
        $this->line('   a connection error. That is expected — the code is in the address bar.');
        $this->newLine();

        $code = (string) $this->ask('3. Paste the value of the `code` parameter');

        if ($code === '') {
            $this->error('No code given.');

            return self::FAILURE;
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => urldecode($code),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => self::REDIRECT,
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            $this->error(sprintf('Token exchange failed (HTTP %d): %s', $response->status(), $response->body()));

            return self::FAILURE;
        }

        $refreshToken = (string) $response->json('refresh_token');

        if ($refreshToken === '') {
            $this->error('Google returned no refresh token. Revoke the app at');
            $this->error('https://myaccount.google.com/permissions and run this again.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Add this line to .env, then run `php artisan cache:clear`:');
        $this->newLine();
        $this->line('GOOGLE_DRIVE_REFRESH_TOKEN='.$refreshToken);

        return self::SUCCESS;
    }
}
