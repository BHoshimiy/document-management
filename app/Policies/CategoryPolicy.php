<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy extends CatalogPolicy
{
    /** Default categories are structural and must not be deleted. */
    public function delete(User $user, ?Category $category = null): bool
    {
        if ($category?->is_default) {
            return false;
        }

        return $user->isAdmin();
    }
}
