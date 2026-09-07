<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->canManageCatalog() || $company->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canManageCatalog() || $user->company()->doesntExist();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->canManageCatalog() || $company->user_id === $user->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }
}
