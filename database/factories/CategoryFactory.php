<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $en = fake()->unique()->words(2, true);

        return [
            'name' => ['en' => $en, 'ru' => $en],
            'slug' => Str::slug($en),
            'order' => fake()->numberBetween(0, 50),
            'is_default' => false,
            'menu_id' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
