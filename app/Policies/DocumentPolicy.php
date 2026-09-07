<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // scoped by Document::visibleTo()
    }

    public function view(User $user, Document $document): bool
    {
        return $user->canManageCatalog() || $document->company->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true; // ownership of the target company is checked in the controller
    }

    public function update(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->view($user, $document);
    }
}
