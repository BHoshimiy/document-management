<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Document;
use App\Models\Menu;
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

it('scopes a category to a standard when one is chosen', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.categories.store'), [
            'name' => ['en' => 'GAP checklists', 'ru' => 'Чек-листы ГАП'],
            'menu_id' => $menu->id,
        ])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::firstWhere('slug', 'gap-checklists')->menu_id)->toBe($menu->id);
});

it('leaves a category global when no standard is chosen', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.categories.store'), [
            'name' => ['en' => 'Staff files', 'ru' => 'Личные дела'],
        ])
        ->assertRedirect(route('admin.categories.index'));

    expect(Category::firstWhere('slug', 'staff-files')->menu_id)->toBeNull();
});

it('rejects an unknown standard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.categories.store'), [
            'name' => ['en' => 'Orphan', 'ru' => 'Сирота'],
            'menu_id' => 999,
        ])
        ->assertSessionHasErrors('menu_id');
});

it('shows the standard of a category on the index', function () {
    Category::factory()
        ->for(Menu::factory()->create(['name' => ['en' => 'GLOBAL GAP', 'ru' => 'ГЛОБАЛ ГАП']]))
        ->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('GLOBAL GAP');
});

/* ---------------- the menu-scoped create flow ---------------- */

it('renders the menu-scoped create form without the menu selector', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.categories.create', $menu))
        ->assertOk()
        ->assertDontSee('name="menu_id"', false)
        ->assertViewHas('category', fn ($category) => $category->menu_id === $menu->id);
});

it('returns to the menu page after creating a category there', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('menus.categories.store', $menu), [
            'name' => ['en' => 'GAP checklists', 'ru' => 'Чек-листы ГАП'],
        ])
        ->assertRedirect(route('menus.show', $menu))
        ->assertSessionHas('status');

    expect(Category::sole())
        ->menu_id->toBe($menu->id)
        ->slug->toBe('gap-checklists');
});

it('ignores a posted menu_id on the menu-scoped store route', function () {
    $menu = Menu::factory()->create();
    $otherMenu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('menus.categories.store', $menu), [
            'menu_id' => $otherMenu->id,
            'name' => ['en' => 'GAP checklists', 'ru' => 'Чек-листы ГАП'],
        ])
        ->assertRedirect(route('menus.show', $menu));

    expect(Category::sole()->menu_id)->toBe($menu->id);
});

it('offers the newly created category as a pill on the menu page', function () {
    $menu = Menu::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('menus.categories.store', $menu), [
            'name' => ['en' => 'GAP checklists', 'ru' => 'Чек-листы ГАП'],
        ]);

    $this->actingAs($admin)
        ->get(route('menus.show', $menu))
        ->assertOk()
        ->assertSee('GAP checklists');
});

it('forbids a client from using the menu-scoped create routes', function () {
    $menu = Menu::factory()->create();
    $client = User::factory()->create();

    $this->actingAs($client)
        ->get(route('menus.categories.create', $menu))
        ->assertForbidden();

    $this->actingAs($client)
        ->post(route('menus.categories.store', $menu), [
            'name' => ['en' => 'GAP checklists', 'ru' => 'Чек-листы ГАП'],
        ])
        ->assertForbidden();

    expect(Category::count())->toBe(0);
});
