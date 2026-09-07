<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Shared policy for reference data: menus, document folders, categories.
 * Everyone signed in may read; only admin/moderator may write; only admin may delete.
 */
class CatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function update(User $user): bool
    {
        return $user->canManageCatalog();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->canManageCatalog();
    }
}
