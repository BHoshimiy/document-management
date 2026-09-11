<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Document Storage
|--------------------------------------------------------------------------
|
| Where uploaded documents live. `local` keeps them on the `public` disk;
| `google_drive` uploads them to Drive under
| <root folder>/<company name>/<document folder name>.
|
| Auth is an OAuth refresh token for a real Google account, so uploaded files
| are owned by that account and use its quota. A service account cannot be used
| on a personal Gmail account: service accounts have zero Drive storage, so
| uploads into My Drive fail with storageQuotaExceeded. Run
| `php artisan drive:authorize` once to mint the refresh token.
|
*/

return [

    'driver' => env('DOCUMENT_STORAGE', 'local'), // local | google_drive

    'google' => [
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),

        // The id out of the folder URL — drive.google.com/drive/folders/<ID> —
        // never the folder's name.
        'root_folder_id' => env('GOOGLE_DRIVE_ROOT_FOLDER_ID'),

        'timeout' => (int) env('GOOGLE_DRIVE_TIMEOUT', 30),
    ],

];
