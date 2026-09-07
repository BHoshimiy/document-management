<?php

declare(strict_types=1);

namespace Database\Seeders;

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
                'folders' => [
                    ['RP-SIT-01', ['en' => 'Site Information & Farm Registration', 'ru' => 'Информация об участке и регистрация']],
                    ['RP-WTR-01', ['en' => 'Water Risk Assessment & Management', 'ru' => 'Оценка рисков и управление водой']],
                    ['RP-FER-01', ['en' => 'Fertilizer Application Record', 'ru' => 'Журнал внесения удобрений']],
                    ['SOP-PPM-01', ['en' => 'Plant Protection Material Management', 'ru' => 'Управление средствами защиты растений']],
                    ['RP-TRC-01', ['en' => 'Traceability & Mass Balance', 'ru' => 'Прослеживаемость и баланс массы']],
                    ['RP-HRV-01', ['en' => 'Harvest Hygiene', 'ru' => 'Гигиена при сборе урожая']],
                    ['RP-TRN-01', ['en' => 'Training Records', 'ru' => 'Записи об обучении']],
                    ['RP-CAL-01', ['en' => 'Equipment Calibration & Maintenance', 'ru' => 'Калибровка и обслуживание оборудования']],
                ],
            ],
            ['slug' => 'iso-22000', 'name' => ['en' => 'ISO 22000', 'ru' => 'ИСО 22000'], 'folders' => []],
            ['slug' => 'halal', 'name' => ['en' => 'HALAL', 'ru' => 'ХАЛЯЛЬ'], 'folders' => []],
        ];

        foreach ($menus as $order => $data) {
            $menu = Menu::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'order' => $order]
            );

            foreach ($data['folders'] as $position => [$code, $name]) {
                DocumentFolder::updateOrCreate(
                    ['menu_id' => $menu->id, 'slug' => Str::slug($name['en'])],
                    ['name' => $name, 'code' => $code, 'order' => $position]
                );
            }
        }
    }
}
