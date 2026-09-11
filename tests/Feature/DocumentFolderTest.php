<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('suffixes the slug when two folders in different standards share a name', function () {
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

    expect(DocumentFolder::pluck('slug')->all())->toBe(['records', 'records-2']);
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

it('offers a folder the global default categories plus the ones of its own standard', function () {
    $folder = DocumentFolder::factory()->create();

    $globalDefault = Category::factory()->default()->create();
    $globalNonDefault = Category::factory()->create();
    $ownStandard = Category::factory()->create(['menu_id' => $folder->menu_id]);
    $otherStandard = Category::factory()->for(Menu::factory())->create();
    $otherStandardDefault = Category::factory()->default()->for(Menu::factory())->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('folders.show', $folder))
        ->assertOk()
        ->assertViewHas('categories', function ($categories) use ($globalDefault, $globalNonDefault, $ownStandard, $otherStandard, $otherStandardDefault) {
            $ids = $categories->pluck('id');

            return $ids->contains($globalDefault->id)
                && $ids->contains($ownStandard->id)
                && ! $ids->contains($globalNonDefault->id)
                && ! $ids->contains($otherStandard->id)
                && ! $ids->contains($otherStandardDefault->id);
        });
});

it('stores a global default category on a folder', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.folders.store'), [
            'menu_id' => $menu->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => 'RP-FER-01',
        ])
        ->assertRedirect(route('admin.folders.index'));

    expect(DocumentFolder::sole()->category_id)->toBe($category->id);
});

it('stores a category of the same menu on a folder', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->create(['menu_id' => $menu->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.folders.store'), [
            'menu_id' => $menu->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => 'RP-FER-01',
        ])
        ->assertRedirect(route('admin.folders.index'));

    expect(DocumentFolder::sole()->category_id)->toBe($category->id);
});

it('refuses a global category that is not a default', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.folders.store'), [
            'menu_id' => $menu->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => 'RP-FER-01',
        ])
        ->assertSessionHasErrors('category_id');

    expect(DocumentFolder::count())->toBe(0);
});

it('refuses a category that belongs to another menu', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->for(Menu::factory())->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.folders.store'), [
            'menu_id' => $menu->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => 'RP-FER-01',
        ])
        ->assertSessionHasErrors('category_id');

    expect(DocumentFolder::count())->toBe(0);
});

it('leaves the folder category empty when none is chosen', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.folders.store'), [
            'menu_id' => Menu::factory()->create()->id,
            'name' => ['en' => 'Records', 'ru' => 'Записи'],
            'code' => 'RP-FER-01',
        ])
        ->assertRedirect(route('admin.folders.index'));

    expect(DocumentFolder::sole()->category_id)->toBeNull();
});

it('shows the folder category on the index', function () {
    DocumentFolder::factory()->create([
        'category_id' => Category::factory()->create([
            'name' => ['en' => 'Staff files', 'ru' => 'Личные дела'],
        ])->id,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.index'))
        ->assertOk()
        ->assertSee('Staff files');
});

it('groups the category options by menu on the create form', function () {
    $menu = Menu::factory()->create(['name' => ['en' => 'GLOBAL GAP', 'ru' => 'ГЛОБАЛ ГАП']]);
    Category::factory()->default()->create(['name' => ['en' => 'Records', 'ru' => 'Записи']]);
    Category::factory()->create([
        'menu_id' => $menu->id,
        'name' => ['en' => 'GAP Checklists', 'ru' => 'Чек-листы ГАП'],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.create'))
        ->assertOk()
        ->assertSee('<optgroup label="GLOBAL GAP">', false)
        ->assertSee('Records')
        ->assertSee('GAP Checklists');
});

it('resolves the folder page by slug', function () {
    $folder = DocumentFolder::factory()->create(['slug' => 'harvest-hygiene']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/folders/harvest-hygiene')
        ->assertOk()
        ->assertViewHas('folder', fn ($f) => $f->is($folder));
});

it('builds the folder url from the slug, never the id', function () {
    $folder = DocumentFolder::factory()->create(['slug' => 'harvest-hygiene']);

    expect(route('folders.show', $folder))->toEndWith('/folders/harvest-hygiene')
        ->and(route('documents.store', $folder))->toEndWith('/folders/harvest-hygiene/documents');
});

it('preselects the menu and category on the create form from the query', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create(['menu_id' => $menu->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.create', ['menu_id' => $menu->id, 'category_id' => $category->id]))
        ->assertOk()
        ->assertViewHas('folder', fn ($folder) => $folder->menu_id === $menu->id
            && $folder->category_id === $category->id);
});

it('leaves the create form unset when no menu or category is passed', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.folders.create'))
        ->assertOk()
        ->assertViewHas('folder', fn ($folder) => $folder->menu_id === null
            && $folder->category_id === null);
});

it('omits the menu field on the menu-scoped create form', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.folders.create', $menu))
        ->assertOk()
        ->assertDontSee('name="menu_id"', false)
        ->assertViewHas('folder', fn ($folder) => $folder->menu_id === $menu->id);
});

it('offers the menu-scoped form only this menus categories and the global defaults', function () {
    $menu = Menu::factory()->create();

    $globalDefault = Category::factory()->default()->create(['name' => ['en' => 'Records', 'ru' => 'Записи']]);
    $ownCategory = Category::factory()->create([
        'menu_id' => $menu->id,
        'name' => ['en' => 'GAP Checklists', 'ru' => 'Чек-листы ГАП'],
    ]);
    $otherMenuCategory = Category::factory()->for(Menu::factory())->create([
        'name' => ['en' => 'Halal Certificates', 'ru' => 'Сертификаты халяль'],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.folders.create', $menu))
        ->assertOk()
        ->assertSee($globalDefault->name)
        ->assertSee($ownCategory->name)
        ->assertDontSee($otherMenuCategory->name);
});

it('preselects the category the tile passed to the menu-scoped form', function () {
    $menu = Menu::factory()->create();
    $category = Category::factory()->default()->create(['menu_id' => $menu->id]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('menus.folders.create', ['menu' => $menu, 'category_id' => $category->id]))
        ->assertOk()
        ->assertViewHas('folder', fn ($folder) => $folder->category_id === $category->id);
});

it('returns to the menu page after creating a folder there', function () {
    $menu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('menus.folders.store', $menu), [
            'name' => ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая'],
            'code' => 'RP-HRV-01',
        ])
        ->assertRedirect(route('menus.show', $menu));

    expect(DocumentFolder::sole())
        ->menu_id->toBe($menu->id)
        ->code->toBe('RP-HRV-01');
});

it('ignores a posted menu_id on the menu-scoped store route', function () {
    $menu = Menu::factory()->create();
    $otherMenu = Menu::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('menus.folders.store', $menu), [
            'menu_id' => $otherMenu->id,
            'name' => ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая'],
            'code' => 'RP-HRV-01',
        ])
        ->assertRedirect(route('menus.show', $menu));

    expect(DocumentFolder::sole()->menu_id)->toBe($menu->id);
});

it('forbids a client from using the menu-scoped create routes', function () {
    $menu = Menu::factory()->create();
    $client = User::factory()->create();

    $this->actingAs($client)
        ->get(route('menus.folders.create', $menu))
        ->assertForbidden();

    $this->actingAs($client)
        ->post(route('menus.folders.store', $menu), [
            'name' => ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая'],
            'code' => 'RP-HRV-01',
        ])
        ->assertForbidden();

    expect(DocumentFolder::count())->toBe(0);
});
