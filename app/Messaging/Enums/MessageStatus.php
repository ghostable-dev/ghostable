<?php

namespace App\Messaging\Enums;

enum MessageStatus: string
{
    case QUEUED = 'queued';
    case SENT = 'sent';
    case SUPPRESSED = 'suppressed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'Queued',
            self::SENT => 'Sent',
            self::SUPPRESSED => 'Suppressed',
            self::FAILED => 'Failed'
        };
    }
}
