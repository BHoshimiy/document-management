<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('only shows a client their own company documents in a folder', function () {
    $user = User::factory()->create();
    $mine = Company::factory()->for($user)->create();
    $folder = DocumentFolder::factory()->create();

    Document::factory()->for($mine)->for($folder)->count(2)->create();
    Document::factory()->for($folder)->count(3)->create(); // other companies

    $this->actingAs($user)
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertViewHas('documents', fn ($docs) => $docs->total() === 2);
});

it('shows every document in a folder to an admin', function () {
    $folder = DocumentFolder::factory()->create();
    Document::factory()->for($folder)->count(4)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertViewHas('documents', fn ($docs) => $docs->total() === 4);
});

it('uploads a file into the folder for the signed-in company', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    Company::factory()->for($user)->create();
    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store', $folder), [
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    Storage::disk('public')->assertExists(Document::latest('id')->first()->path);
});

it('blocks downloading another company document', function () {
    $document = Document::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('documents.download', $document))
        ->assertForbidden();
});

it('lets an admin upload on a chosen companys behalf', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('documents.store', $folder), [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $document = Document::sole();

    expect($document->company_id)->toBe($company->id)
        ->and($document->document_folder_id)->toBe($folder->id);

    Storage::disk('public')->assertExists($document->path);
});

it('lets a moderator upload on a chosen companys behalf', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->post(route('documents.store', $folder), [
            'company_id' => $company->id,
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect(Document::sole()->company_id)->toBe($company->id);
});

it('requires a catalog manager to choose a company', function () {
    Storage::fake('public');

    Company::factory()->create();
    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('documents.store', $folder), [
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertSessionHasErrors('company_id');

    expect(Document::count())->toBe(0);
});

it('ignores a company_id posted by a client', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $own = Company::factory()->for($user)->create();
    $other = Company::factory()->create();
    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store', $folder), [
            'company_id' => $other->id,
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertRedirect();

    expect(Document::sole()->company_id)->toBe($own->id);
});

it('forbids uploading for a user with no company', function () {
    Storage::fake('public');

    $folder = DocumentFolder::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('documents.store', $folder), [
            'category_id' => $category->id,
            'file' => UploadedFile::fake()->create('report.pdf', 120, 'application/pdf'),
        ])
        ->assertForbidden();

    expect(Document::count())->toBe(0);
});

it('shows the upload card to an admin only when a company exists', function () {
    $folder = DocumentFolder::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertDontSee('upload-card');

    Company::factory()->create();

    $this->actingAs($admin)
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertSee('upload-card');
});
