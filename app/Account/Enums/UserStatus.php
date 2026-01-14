<?php

namespace App\Account\Enums;

enum UserStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case LOCKED = 'locked';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::LOCKED => 'Locked',
        };
    }

    public function is(self $status): bool
    {
        return $this === $status;
    }
}
