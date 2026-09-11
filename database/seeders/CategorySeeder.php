<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
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
    }
}
