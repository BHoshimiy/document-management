<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CompanyCertificate;
use App\Models\User;

class CompanyCertificatePolicy
{
    public function view(User $user, CompanyCertificate $certificate): bool
    {
        return $user->canManageCatalog() || $certificate->company->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CompanyCertificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function delete(User $user, CompanyCertificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }
}
