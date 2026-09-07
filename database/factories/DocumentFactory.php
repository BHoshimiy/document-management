<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->slug(3).'.pdf';

        return [
            'company_id' => Company::factory(),
            'category_id' => Category::factory(),
            'document_folder_id' => DocumentFolder::factory(),
            'name' => $name,
            'path' => 'companies/1/folders/1/'.$name,
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10_000, 5_000_000),
        ];
    }
}
