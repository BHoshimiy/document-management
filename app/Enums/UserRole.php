<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Moderator = 'moderator';
    case Client = 'client';

    /** Roles allowed to manage reference data (menus, folders, categories). */
    public function canManageCatalog(): bool
    {
        return in_array($this, [self::Admin, self::Moderator], true);
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
