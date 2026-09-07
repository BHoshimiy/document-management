<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blocked = 'blocked';

    public function canLogin(): bool
    {
        return $this === self::Active;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
