<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use App\Services\DocumentService;
use App\Services\GoogleDriveClient;
use App\Services\Storage\GoogleDriveStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'documents.driver' => 'google_drive',
        'documents.google.client_id' => 'CLIENT-ID',
        'documents.google.client_secret' => 'CLIENT-SECRET',
        'documents.google.refresh_token' => 'REFRESH-TOKEN',
        'documents.google.root_folder_id' => 'ROOT',
    ]);

    // Pre-seed the cached token so no test needs to fake the OAuth exchange.
    // The key is derived from the credentials, exactly as the client does it.
    cache()->put(
        'google-drive.token.'.sha1('CLIENT-ID|REFRESH-TOKEN'),
        'fake-token',
        now()->addMinutes(55),
    );
});

/**
 * Drive routes several operations through the same URL, so the fake has to
 * branch on the method rather than on a URL pattern.
 *
 * @param  array<int, array<string, string>>  $existingFolders
 */
function fakeDrive(array $existingFolders = []): void
{
    Http::fake(function (Request $request) use ($existingFolders) {
        if (str_contains($request->url(), '/upload/drive/v3/files')) {
            return Http::response(['id' => 'FILE-1']);
        }

        return match ($request->method()) {
            'POST' => Http::response(['id' => 'NEW-FOLDER']),
            'DELETE' => Http::response([], 204),
            default => Http::response(['files' => $existingFolders]),
        };
    });
}

function uploadAsAdmin(DocumentFolder $folder, Company $company): TestResponse
{
    return test()->actingAs(User::factory()->admin()->create())
        ->post(route('documents.store', $folder), [
            'company_id' => $company->id,
            'category_id' => Category::factory()->create()->id,
            'file' => UploadedFile::fake()->create('report.pdf', 12, 'application/pdf'),
        ]);
}

it('creates the company and folder in drive when neither exists', function () {
    fakeDrive();

    $company = Company::factory()->create(['name' => 'icofex Demo Farm']);
    $folder = DocumentFolder::factory()->create([
        'name' => ['en' => 'Fertilizer Application Record', 'ru' => 'Журнал внесения удобрений'],
    ]);

    uploadAsAdmin($folder, $company)->assertRedirect();

    expect(Document::sole())
        ->drive_file_id->toBe('FILE-1')
        ->path->toBeNull();

    // The company folder is created under the configured root...
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && ($r->data()['name'] ?? null) === 'icofex Demo Farm'
        && ($r->data()['parents'] ?? []) === ['ROOT']);

    // ...and the document folder inside it.
    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && ($r->data()['name'] ?? null) === 'Fertilizer Application Record'
        && ($r->data()['parents'] ?? []) === ['NEW-FOLDER']);
});

it('reuses drive folders that already exist', function () {
    fakeDrive([['id' => 'EXISTING', 'name' => 'whatever']]);

    uploadAsAdmin(DocumentFolder::factory()->create(), Company::factory()->create())->assertRedirect();

    // Nothing was created; the only POST is the upload itself.
    Http::assertNotSent(fn (Request $r) => $r->method() === 'POST'
        && ! str_contains($r->url(), '/upload/'));

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/upload/drive/v3/files'));
});

it('escapes a single quote in a company name', function () {
    fakeDrive();

    uploadAsAdmin(
        DocumentFolder::factory()->create(),
        Company::factory()->create(['name' => "O'Brien Farms"]),
    )->assertRedirect();

    Http::assertSent(fn (Request $r) => $r->method() === 'GET'
        && str_contains(urldecode($r->url()), "name = 'O\\'Brien Farms'"));
});

it('names the drive folder in english whatever the ui locale', function () {
    fakeDrive();

    $folder = DocumentFolder::factory()->create([
        'name' => ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая'],
    ]);

    app()->setLocale('ru');

    uploadAsAdmin($folder, Company::factory()->create())->assertRedirect();

    Http::assertSent(fn (Request $r) => $r->method() === 'POST'
        && ($r->data()['name'] ?? null) === 'Harvest Hygiene');
});

it('streams a drive document through the app', function () {
    Http::fake(['www.googleapis.com/drive/v3/files/*' => Http::response('PDF-BYTES')]);

    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    $document = Document::factory()->for($company)->create(['drive_file_id' => 'FILE-1', 'path' => null]);

    expect($this->actingAs($user)->get(route('documents.download', $document))->streamedContent())
        ->toBe('PDF-BYTES');

    // The proxy still runs DocumentPolicy: another company's client is refused.
    $this->actingAs(User::factory()->create())
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

it('deletes the drive file through the drive storage', function () {
    fakeDrive();

    $document = Document::factory()->create(['drive_file_id' => 'FILE-1', 'path' => null]);

    app(GoogleDriveStorage::class)->delete($document);

    Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
        && str_contains($r->url(), '/drive/v3/files/FILE-1'));
});

it('keeps the drive file when a document is only soft deleted', function () {
    fakeDrive();

    $document = Document::factory()->create(['drive_file_id' => 'FILE-1', 'path' => null]);

    app(DocumentService::class)->delete($document);

    expect($document->trashed())->toBeTrue();

    Http::assertNotSent(fn (Request $r) => $r->method() === 'DELETE');
});

it('still serves a locally stored document after the switch flips to drive', function () {
    Storage::fake('public');
    Http::fake();

    $user = User::factory()->create();
    $company = Company::factory()->for($user)->create();
    Storage::disk('public')->put('companies/1/folders/1/legacy.pdf', 'LOCAL-BYTES');

    $document = Document::factory()->for($company)->create([
        'path' => 'companies/1/folders/1/legacy.pdf',
        'drive_file_id' => null,
        'name' => 'legacy.pdf',
    ]);

    expect($this->actingAs($user)->get(route('documents.download', $document))->streamedContent())
        ->toBe('LOCAL-BYTES');

    // A local row must never reach Drive, even with the driver switched.
    Http::assertNothingSent();
});

it('exchanges the refresh token for an access token', function () {
    cache()->flush();

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'oauth2.googleapis.com/token')) {
            return Http::response(['access_token' => 'minted', 'expires_in' => 3599]);
        }

        return Http::response(['files' => []]);
    });

    app(GoogleDriveClient::class)->findFolder('anything', 'ROOT');

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'oauth2.googleapis.com/token')
        && $r['grant_type'] === 'refresh_token'
        && $r['client_id'] === 'CLIENT-ID'
        && $r['client_secret'] === 'CLIENT-SECRET'
        && $r['refresh_token'] === 'REFRESH-TOKEN');

    // The bearer token from that exchange is what the Drive call carries.
    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/drive/v3/files')
        && $r->hasHeader('Authorization', 'Bearer minted'));
});

it('explains a 404 as a wrong root folder id', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'File not found: .']], 404)]);

    config(['documents.google.root_folder_id' => 'document-management']);

    expect(fn () => app(GoogleDriveClient::class)->findFolder('x', 'document-management'))
        ->toThrow(
            RuntimeException::class,
            'GOOGLE_DRIVE_ROOT_FOLDER_ID is currently [document-management]',
        );
});

it('fails before any request when a credential is missing', function () {
    cache()->flush();
    Http::fake();

    config(['documents.google.refresh_token' => null]);

    expect(fn () => app(GoogleDriveClient::class)->findFolder('x', 'ROOT'))
        ->toThrow(RuntimeException::class, 'GOOGLE_DRIVE_REFRESH_TOKEN is not set');

    Http::assertNothingSent();
});
