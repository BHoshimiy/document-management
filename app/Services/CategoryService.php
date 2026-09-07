<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $data['order'] ??= (int) Category::max('order') + 1;

            return Category::create($data);
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $category->update($data);

            return $category->refresh();
        });
    }

    /** @param array<int, int> $orderedIds category ids in their new display order */
    public function reorder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $position => $id) {
                Category::whereKey($id)->update(['order' => $position]);
            }
        });
    }
}
