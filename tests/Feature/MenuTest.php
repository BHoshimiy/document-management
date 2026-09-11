<?php

declare(strict_types=1);

use App\Models\Category;
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
})->skip(fn () => ! onPostgres(), 'Search uses ilike, which only PostgreSQL supports.');

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

it('offers the global default categories plus the menus own as tab pills', function () {
    $menu = Menu::factory()->create();

    $globalDefault = Category::factory()->default()->create();
    $ownDefault = Category::factory()->default()->create(['menu_id' => $menu->id]);
    $globalNonDefault = Category::factory()->create();
    $otherMenuDefault = Category::factory()->default()->for(Menu::factory())->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.show', $menu))
        ->assertOk()
        ->assertViewHas('categories', function ($categories) use ($globalDefault, $ownDefault, $globalNonDefault, $otherMenuDefault) {
            $ids = $categories->pluck('id');

            return $ids->contains($globalDefault->id)
                && $ids->contains($ownDefault->id)
                && ! $ids->contains($globalNonDefault->id)
                && ! $ids->contains($otherMenuDefault->id);
        });
});

it('filters the folders of a menu by category', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create(['menu_id' => $menu->id]);

    $matching = DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'category_id' => $category->id,
    ]);
    DocumentFolder::factory()->create(['menu_id' => $menu->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.show', ['menu' => $menu, 'category' => $category->slug]))
        ->assertOk()
        ->assertViewHas('folders', fn ($folders) => $folders->count() === 1
            && $folders->first()->id === $matching->id);
});

it('filters the folders of a menu by name', function () {
    $menu = Menu::factory()->create();

    DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'name' => ['en' => 'Pesticide log', 'ru' => 'Журнал пестицидов'],
        'slug' => 'pesticide-log',
    ]);
    DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'name' => ['en' => 'Water analysis', 'ru' => 'Анализ воды'],
        'slug' => 'water-analysis',
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.show', ['menu' => $menu, 'search' => 'Pesticide']))
        ->assertOk()
        ->assertViewHas('folders', fn ($folders) => $folders->count() === 1
            && $folders->first()->slug === 'pesticide-log');
})->skip(fn () => ! onPostgres(), 'Search uses ilike, which only PostgreSQL supports.');

it('combines the category and name filters and ignores an unknown category', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create(['menu_id' => $menu->id]);

    $matching = DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'category_id' => $category->id,
        'name' => ['en' => 'Harvest record', 'ru' => 'Запись урожая'],
        'slug' => 'harvest-record',
    ]);
    // Right category, wrong name.
    DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'category_id' => $category->id,
        'name' => ['en' => 'Storage record', 'ru' => 'Запись хранения'],
        'slug' => 'storage-record',
    ]);
    // Right name, no category.
    DocumentFolder::factory()->create([
        'menu_id' => $menu->id,
        'name' => ['en' => 'Harvest plan', 'ru' => 'План урожая'],
        'slug' => 'harvest-plan',
    ]);

    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('menus.show', ['menu' => $menu, 'category' => $category->slug, 'search' => 'Harvest']))
        ->assertOk()
        ->assertViewHas('folders', fn ($folders) => $folders->count() === 1
            && $folders->first()->id === $matching->id);

    $this->actingAs($user)
        ->get(route('menus.show', ['menu' => $menu, 'category' => 'does-not-exist']))
        ->assertOk()
        ->assertViewHas('activeCategory', fn ($activeCategory) => $activeCategory === null)
        ->assertViewHas('folders', fn ($folders) => $folders->count() === 3);
})->skip(fn () => ! onPostgres(), 'Search uses ilike, which only PostgreSQL supports.');

it('shows the create-folder tile to a moderator but not to a client', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('menus.show', $menu))
        ->assertOk()
        ->assertSee('add-card');

    $this->actingAs(User::factory()->create())
        ->get(route('menus.show', $menu))
        ->assertOk()
        ->assertDontSee('add-card');
});

it('points the create-folder tile at the menu and the active category', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create(['menu_id' => $menu->id]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('menus.show', ['menu' => $menu, 'category' => $category->slug]))
        ->assertOk()
        // assertSee escapes, matching the &amp; Blade writes into the href.
        ->assertSee(route('menus.folders.create', [
            'menu' => $menu,
            'category_id' => $category->id,
        ]));

    // With no pill active the tile carries only the menu.
    $this->actingAs($admin)
        ->get(route('menus.show', $menu))
        ->assertOk()
        ->assertSee(route('menus.folders.create', ['menu' => $menu]))
        ->assertDontSee('category_id=');
});
