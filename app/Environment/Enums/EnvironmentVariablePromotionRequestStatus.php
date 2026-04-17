<?php

declare(strict_types=1);

namespace App\Environment\Enums;

enum EnvironmentVariablePromotionRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Approved, self::Rejected, self::Cancelled => true,
            self::Pending => false,
        };
    }
}
