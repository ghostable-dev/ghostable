<?php

namespace App\Team\Notifications;

use App\Team\Models\Team;
use App\Team\Models\TeamInvite;

class MemberInvitedNotification extends MembershipActivityNotification
{
    public function __construct(
        public TeamInvite $invite
    ) {}
    
    public function forTeam(): Team
    {
        return $this->invite->team;
    }

    protected function subject(): string
    {
        return "{$this->invite->email} invited to {$this->invite->team->name}";
    }

    protected function messageLine(): string
    {
        return "{$this->invite->user->email} invited {$this->invite->email} to the team \"{$this->invite->team->name}\".";
    }
}
