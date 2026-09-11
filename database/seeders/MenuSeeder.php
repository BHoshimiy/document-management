<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DocumentFolder;
use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'slug' => 'global-gap',
                'name' => ['en' => 'GLOBAL GAP', 'ru' => 'ГЛОБАЛ ГАП'],
                'categories' => [
                    ['gap-checklists', ['en' => 'GAP Checklists', 'ru' => 'Чек-листы ГАП'], 7],
                ],
                'folders' => [
                    ['RP-SIT-01', ['en' => 'Site Information & Farm Registration', 'ru' => 'Информация об участке и регистрация'], 'documents'],
                    ['RP-WTR-01', ['en' => 'Water Risk Assessment & Management', 'ru' => 'Оценка рисков и управление водой'], 'records'],
                    ['RP-FER-01', ['en' => 'Fertilizer Application Record', 'ru' => 'Журнал внесения удобрений'], 'records'],
                    ['SOP-PPM-01', ['en' => 'Plant Protection Material Management', 'ru' => 'Управление средствами защиты растений'], 'sops'],
                    ['RP-TRC-01', ['en' => 'Traceability & Mass Balance', 'ru' => 'Прослеживаемость и баланс массы'], 'records'],
                    ['RP-HRV-01', ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая'], 'gap-checklists'],
                    ['RP-TRN-01', ['en' => 'Training Records', 'ru' => 'Записи об обучении'], 'staff-files'],
                    ['RP-CAL-01', ['en' => 'Equipment Calibration & Maintenance', 'ru' => 'Калибровка и обслуживание оборудования'], 'hardware'],
                ],
            ],
            [
                'slug' => 'iso-22000',
                'name' => ['en' => 'ISO 22000', 'ru' => 'ИСО 22000'],
                'categories' => [],
                'folders' => [],
            ],
            [
                'slug' => 'halal',
                'name' => ['en' => 'HALAL', 'ru' => 'ХАЛЯЛЬ'],
                'categories' => [
                    ['halal-certificates', ['en' => 'Halal Certificates', 'ru' => 'Сертификаты халяль'], 8],
                ],
                'folders' => [],
            ],
        ];

        foreach ($menus as $order => $data) {
            $menu = Menu::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'order' => $order]
            );

            // Menu-scoped categories: only offered in folders of this menu.
            foreach ($data['categories'] as [$slug, $name, $categoryOrder]) {
                Category::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'order' => $categoryOrder, 'is_default' => false, 'menu_id' => $menu->id]
                );
            }

            // forMenu() is the rule DocumentFolderRequest enforces on user input:
            // a folder may only carry a global category or one of its own menu.
            $available = Category::query()->forMenu($menu->id)->get()->keyBy('slug');

            foreach ($data['folders'] as $position => [$code, $name, $categorySlug]) {
                DocumentFolder::updateOrCreate(
                    ['menu_id' => $menu->id, 'slug' => Str::slug($name['en'])],
                    [
                        'name' => $name,
                        'code' => $code,
                        'order' => $position,
                        'category_id' => $available->get($categorySlug)?->id,
                    ]
                );
            }
        }
    }
}
