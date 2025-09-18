<?php

namespace App\Messaging\Enums;

enum CampaignType: string
{
    case DRIP = 'drip';
    case BROADCAST = 'broadcast';

    public function label(): string
    {
        return match ($this) {
            self::DRIP => 'Drip',
            self::BROADCAST => 'Broadcast'
        };
    }
}
