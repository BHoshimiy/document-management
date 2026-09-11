<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\DocumentFolder;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gives every seeded folder a category that is global or belongs to its own menu', function () {
    $this->seed();

    $folders = DocumentFolder::with('category')->get();

    expect($folders)->not->toBeEmpty();

    foreach ($folders as $folder) {
        expect($folder->category)->not->toBeNull();
        expect($folder->category->menu_id === null || $folder->category->menu_id === $folder->menu_id)
            ->toBeTrue();
    }
});

it('keeps the menu-scoped categories attached to their own menu', function () {
    $this->seed();

    $gapChecklists = Category::firstWhere('slug', 'gap-checklists');
    $halalCertificates = Category::firstWhere('slug', 'halal-certificates');

    expect($gapChecklists->menu_id)->toBe(Menu::firstWhere('slug', 'global-gap')->id)
        ->and($gapChecklists->is_default)->toBeFalse()
        ->and($halalCertificates->menu_id)->toBe(Menu::firstWhere('slug', 'halal')->id)
        ->and($halalCertificates->is_default)->toBeFalse()
        ->and(Category::defaults()->count())->toBe(7);
});
