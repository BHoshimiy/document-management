<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists categories for an admin', function () {
    Category::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertViewHas('categories');
});

it('forbids a client from opening the create form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.categories.create'))
        ->assertForbidden();
});

it('lets a moderator create a category and auto-generates the slug', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->post(route('admin.categories.store'), [
            'name' => ['en' => 'Staff files', 'ru' => 'Личные дела'],
        ])
        ->assertRedirect(route('admin.categories.index'))
        ->assertSessionHas('status');

    expect(Category::firstWhere('slug', 'staff-files'))->not->toBeNull();
});

it('requires both locales on the name', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.categories.store'), ['name' => ['en' => 'Only English']])
        ->assertSessionHasErrors('name.ru');
});

it('updates a category', function () {
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.categories.update', $category), [
            'name' => ['en' => 'Renamed', 'ru' => 'Переименовано'],
        ])
        ->assertRedirect(route('admin.categories.index'));

    expect($category->refresh()->translate('name', 'ru'))->toBe('Переименовано')
        ->and($category->slug)->toBe('renamed');
});

it('refuses to delete a category that still has documents', function () {
    $category = Category::factory()->create();
    Document::factory()->for($category)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('category');
});

it('never deletes a default category', function () {
    $category = Category::factory()->default()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.categories.destroy', $category))
        ->assertForbidden();
});

it('renders the categories index as a table', function () {
    Category::factory()->create(['name' => ['en' => 'Staff files', 'ru' => 'Личные дела']]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('uploads-table')
        ->assertDontSee('doc-card')
        ->assertSee('Staff files');
});
