<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFolderFactory extends Factory
{
    public function definition(): array
    {
        $en = fake()->unique()->words(3, true);

        return [
            'menu_id' => Menu::factory(),
            'name' => ['en' => $en, 'ru' => $en],
            'code' => strtoupper(fake()->bothify('??-###')),
            'slug' => Str::slug($en),
            'order' => fake()->numberBetween(0, 30),
        ];
    }
}
