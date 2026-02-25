<?php

declare(strict_types=1);

namespace App\Environment\Enums;

enum EnvironmentKeyReshareRequestStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Superseded = 'superseded';

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }
}
