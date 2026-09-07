<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives folders in different standards the same slug', function () {
    $admin = User::factory()->admin()->create();

    foreach (Menu::factory()->count(2)->create() as $menu) {
        $this->actingAs($admin)
            ->post(route('admin.folders.store'), [
                'menu_id' => $menu->id,
                'name' => ['en' => 'Records', 'ru' => 'Записи'],
                'code' => 'RP-FER-01',
            ])
            ->assertRedirect(route('admin.folders.index'));
    }

    expect(DocumentFolder::pluck('slug')->all())->toBe(['records', 'records']);
});

it('suffixes the slug for two folders in the same standard', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create();

    foreach ([1, 2] as $_) {
        $this->actingAs($admin)
            ->post(route('admin.folders.store'), [
                'menu_id' => $menu->id,
                'name' => ['en' => 'Records', 'ru' => 'Записи'],
                'code' => 'RP-FER-01',
            ])
            ->assertRedirect(route('admin.folders.index'));
    }

    expect(DocumentFolder::pluck('slug')->all())->toBe(['records', 'records-2']);
});

it('keeps a folder slug when it is re-saved unchanged', function () {
    $folder = DocumentFolder::factory()->create([
        'name' => ['en' => 'Records', 'ru' => 'Записи'],
        'slug' => 'records',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.folders.update', $folder), [
            'menu_id' => $folder->menu_id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => $folder->code,
        ])
        ->assertRedirect(route('admin.folders.index'));

    expect($folder->refresh()->slug)->toBe('records');
});

it('deletes a folder that has no documents', function () {
    $folder = DocumentFolder::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.folders.destroy', $folder))
        ->assertRedirect(route('admin.folders.index'))
        ->assertSessionHas('status');

    expect($folder->refresh()->trashed())->toBeTrue();
});

it('refuses to delete a folder that still has documents', function () {
    $folder = DocumentFolder::factory()->create();
    Document::factory()->for($folder)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.folders.destroy', $folder))
        ->assertSessionHasErrors('folder');

    expect($folder->refresh()->trashed())->toBeFalse();
});

it('forbids a moderator from deleting a folder', function () {
    $folder = DocumentFolder::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('admin.folders.destroy', $folder))
        ->assertForbidden();

    expect($folder->refresh()->trashed())->toBeFalse();
});

it('shows the delete button only to an admin', function () {
    DocumentFolder::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.index'))
        ->assertOk()
        ->assertSee('btn-pin-danger');

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('admin.folders.index'))
        ->assertOk()
        ->assertDontSee('btn-pin-danger');
});

it('renders the folders index as a table', function () {
    DocumentFolder::factory()->create([
        'name' => ['en' => 'Fertilizer Record', 'ru' => 'Записи удобрений'],
        'code' => 'RP-FER-01',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.index'))
        ->assertOk()
        ->assertSee('uploads-table')
        ->assertDontSee('doc-card')
        ->assertSee('RP-FER-01')
        ->assertSee('Fertilizer Record');
});
