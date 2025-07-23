<?php

namespace App\Secret\Enums;

enum SecretNotification: string
{
    case SECRET_UPDATED = 'secret_updated';

    public function label(): string
    {
        return match ($this) {
            self::SECRET_UPDATED => 'Secret Updated',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SECRET_UPDATED => 'A secret was updated.',
        };
    }
}
