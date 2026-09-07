<?php

declare(strict_types=1);

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
    $category = \App\Models\Category::factory()->create();

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
