<?php

namespace App\Team\Notifications;

use App\Team\Models\TeamInvite;
use App\Team\Notifications\MembershipActivityNotification;

class MemberJoinedNotification extends MembershipActivityNotification
{
    public function __construct(
        public TeamInvite $invite
    ) {}
        
    protected function subject(): string
    {
        return "{$this->invite->email} joined the {$this->invite->team->name} team.";
    }

    protected function messageLine(): string
    {
        return "{$this->invite->email} joined the team \"{$this->invite->team->name}\".";
    }
}