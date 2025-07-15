<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;

abstract class TeamMembershipAction
{
    protected function cacheKeyForMembership(Team $team, User $user): string
    {
        return "team:{$team->id}:user:{$user->id}:belongs";
    }

    protected function cacheKeyForMembershipRecord(Team $team, User $user): string
    {
        return "teamMembership:{$team->id}:user:{$user->id}";
    }
}
