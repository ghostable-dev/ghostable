<?php

namespace App\Team\Events;

use App\Account\Models\User;
use App\Team\Models\Team;

class MemberEvent extends TeamEvent
{
    public function __construct(public Team $team, public User $user)
    {
        parent::__construct($team);
    }
}
