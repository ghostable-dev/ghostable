<?php

namespace App\Organization\Enums;

enum InviteStatus: string
{
    case ACCEPTED = 'accepted';
    case EXPIRED = 'expired';
    case PENDING = 'pending';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::ACCEPTED => 'Accepted',
            self::EXPIRED => 'Expired',
            self::PENDING => 'Pending'
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACCEPTED => 'green',
            self::EXPIRED => 'red',
            self::PENDING => 'amber'
        };
    }

    public function is(self $status): bool
    {
        return $this === $status;
    }
}
