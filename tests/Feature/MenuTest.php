<?php

declare(strict_types=1);

use App\Models\DocumentFolder;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists standards for an admin', function () {
    Menu::factory()->count(3)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertViewHas('menus');
});

it('forbids a client from opening the create form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.menus.create'))
        ->assertForbidden();
});

it('lets a moderator create a standard and auto-generates the slug', function () {
    $this->actingAs(User::factory()->moderator()->create())
        ->post(route('admin.menus.store'), [
            'name' => ['en' => 'Global GAP', 'ru' => 'Глобал ГАП'],
        ])
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('status');

    expect(Menu::firstWhere('slug', 'global-gap'))->not->toBeNull();
});

it('requires both locales on the name', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.menus.store'), ['name' => ['en' => 'Only English']])
        ->assertSessionHasErrors('name.ru');
});

it('updates a standard', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.menus.update', $menu), [
            'name' => ['en' => 'Renamed', 'ru' => 'Переименовано'],
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($menu->refresh()->translate('name', 'ru'))->toBe('Переименовано');
});

it('refuses to delete a standard that still has folders', function () {
    $menu = Menu::factory()->create();
    DocumentFolder::factory()->for($menu)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.menus.destroy', $menu))
        ->assertSessionHasErrors('menu');

    expect($menu->refresh()->trashed())->toBeFalse();
});

it('deletes a standard that has no folders', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.menus.destroy', $menu))
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('status');

    expect($menu->refresh()->trashed())->toBeTrue();
});

it('forbids a moderator from deleting a standard', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->delete(route('admin.menus.destroy', $menu))
        ->assertForbidden();
});

it('reorders standards for an admin', function () {
    $first = Menu::factory()->create(['order' => 5]);
    $second = Menu::factory()->create(['order' => 9]);

    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.menus.index'))
        ->post(route('admin.menus.reorder'), ['ids' => [$second->id, $first->id]])
        ->assertRedirect(route('admin.menus.index'))
        ->assertSessionHas('status');

    expect($second->refresh()->order)->toBe(0)
        ->and($first->refresh()->order)->toBe(1);
});

it('filters the index by search term', function () {
    Menu::factory()->create(['name' => ['en' => 'Halal', 'ru' => 'Халяль'], 'slug' => 'halal']);
    Menu::factory()->create(['name' => ['en' => 'Kosher', 'ru' => 'Кошер'], 'slug' => 'kosher']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.menus.index', ['search' => 'Halal']))
        ->assertOk()
        ->assertViewHas('menus', fn ($menus) => $menus->count() === 1
            && $menus->first()->slug === 'halal');
});

it('regenerates the slug when the english name changes', function () {
    $menu = Menu::factory()->create(['name' => ['en' => 'Records', 'ru' => 'Записи'], 'slug' => 'records']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.menus.update', $menu), [
            'name' => ['en' => 'Audit Records', 'ru' => 'Записи аудита'],
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($menu->refresh()->slug)->toBe('audit-records');
});

it('ignores a slug posted in the form', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.menus.store'), [
            'name' => ['en' => 'Global GAP', 'ru' => 'Глобал ГАП'],
            'slug' => 'hand-written',
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect(Menu::firstWhere('slug', 'global-gap'))->not->toBeNull()
        ->and(Menu::firstWhere('slug', 'hand-written'))->toBeNull();
});

it('suffixes the slug when two standards share an english name', function () {
    $admin = User::factory()->admin()->create();

    foreach ([1, 2] as $_) {
        $this->actingAs($admin)
            ->post(route('admin.menus.store'), [
                'name' => ['en' => 'Records', 'ru' => 'Записи'],
            ])
            ->assertRedirect(route('admin.menus.index'));
    }

    expect(Menu::pluck('slug')->all())->toBe(['records', 'records-2']);
});

it('keeps its own slug when re-saved unchanged', function () {
    $menu = Menu::factory()->create(['name' => ['en' => 'Records', 'ru' => 'Записи'], 'slug' => 'records']);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.menus.update', $menu), [
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect($menu->refresh()->slug)->toBe('records');
});

it('reuses a soft-deleted standard name without hitting the unique index', function () {
    $admin = User::factory()->admin()->create();
    $menu = Menu::factory()->create(['name' => ['en' => 'Records', 'ru' => 'Записи'], 'slug' => 'records']);

    $this->actingAs($admin)->delete(route('admin.menus.destroy', $menu))->assertSessionHas('status');

    $this->actingAs($admin)
        ->post(route('admin.menus.store'), [
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
        ])
        ->assertRedirect(route('admin.menus.index'));

    expect(Menu::firstWhere('slug', 'records-2'))->not->toBeNull();
});

it('renders the standards index as a table', function () {
    $menu = Menu::factory()->create(['name' => ['en' => 'Global GAP', 'ru' => 'Глобал ГАП'], 'slug' => 'global-gap']);
    DocumentFolder::factory()->for($menu)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.menus.index'))
        ->assertOk()
        ->assertSee('uploads-table')
        ->assertDontSee('doc-card')
        ->assertSee('Global GAP')
        ->assertSee('1 folder');
});
