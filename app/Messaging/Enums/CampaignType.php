<?php

namespace App\Messaging\Enums;

enum CampaignType: string
{
    case DRIP_USERS = 'drip_users';          // typical onboarding drips
    case BROADCAST_USERS = 'broadcast_users'; // product update to app users
    case BROADCAST_LIST = 'broadcast_list';   // newsletter / leads list
    case BROADCAST_ALL = 'broadcast_all';     // both users + list

    public function label(): string
    {
        return match ($this) {
            self::DRIP_USERS => 'Drip (Users)',
            self::BROADCAST_USERS => 'Broadcast (Users)',
            self::BROADCAST_LIST => 'Broadcast (List)',
            self::BROADCAST_ALL => 'Broadcast (Users + List)',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::DRIP_USERS => 'Drip',
            self::BROADCAST_USERS,
            self::BROADCAST_LIST,
            self::BROADCAST_ALL => 'Broadcast',
        };
    }
}
