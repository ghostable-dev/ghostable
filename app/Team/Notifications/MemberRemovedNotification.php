<?php

namespace App\Team\Notifications;

use App\Account\Models\User;
use App\Team\Models\Team;
use App\Team\Notifications\MembershipActivityNotification;

class MemberRemovedNotification extends MembershipActivityNotification
{
    public function __construct(
        public Team $team,
        public User $user
    ) {}
    
    protected function subject(): string
    {
        return "{$this->user->email} removed from {$this->team->name}";
    }

    protected function messageLine(): string
    {
        return "{$this->user->email} removed from the team \"{$this->team->name}\".";
    }
}