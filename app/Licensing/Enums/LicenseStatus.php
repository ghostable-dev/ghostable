<?php

declare(strict_types=1);

namespace App\Licensing\Enums;

enum LicenseStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Revoked = 'revoked';
    case Refunded = 'refunded';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Revoked => 'Revoked',
            self::Refunded => 'Refunded',
            self::Expired => 'Expired',
        };
    }
}
