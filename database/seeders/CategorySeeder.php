<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['records',     ['en' => 'Records',     'ru' => 'Записи']],
            ['sops',        ['en' => 'SOPs',        'ru' => 'СОП']],
            ['policy',      ['en' => 'Policy',      'ru' => 'Политика']],
            ['hardware',    ['en' => 'Hardware',    'ru' => 'Оборудование']],
            ['photos',      ['en' => 'Photos',      'ru' => 'Фотографии']],
            ['staff-files', ['en' => 'Staff files', 'ru' => 'Личные дела']],
            ['documents',   ['en' => 'Documents',   'ru' => 'Документы']],
        ];

        foreach ($categories as $order => [$slug, $name]) {
            Category::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'order' => $order, 'is_default' => true, 'menu_id' => null]
            );
        }

        // Standard-scoped examples: only offered in folders of that standard.
        $scoped = [
            ['global-gap', 'gap-checklists',      ['en' => 'GAP Checklists',       'ru' => 'Чек-листы ГАП']],
            ['halal',      'halal-certificates',  ['en' => 'Halal Certificates',   'ru' => 'Сертификаты халяль']],
        ];

        foreach ($scoped as $position => [$menuSlug, $slug, $name]) {
            $menu = Menu::firstWhere('slug', $menuSlug);

            if ($menu === null) {
                continue;
            }

            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'order' => count($categories) + $position,
                    'is_default' => false,
                    'menu_id' => $menu->id,
                ]
            );
        }
    }
}
