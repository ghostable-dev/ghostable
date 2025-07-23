<?php

namespace App\Team\Notifications;

use App\Team\Models\Team;
use App\Team\Models\TeamInvite;

class MemberJoinedNotification extends MembershipActivityNotification
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
        return "{$this->invite->email} joined the {$this->invite->team->name} team.";
    }

    protected function messageLine(): string
    {
        return "{$this->invite->email} joined the team \"{$this->invite->team->name}\".";
    }
}
