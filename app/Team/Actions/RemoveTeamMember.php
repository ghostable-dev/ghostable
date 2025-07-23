<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Events\MemberRemoved;
use App\Team\Models\Team;

class RemoveTeamMember
{
    public function handle(User $member, Team $team): Team
    {
        $member->teamMembership()->removeFromTeam($team);

        MemberRemoved::dispatch($team, $member);

        return $team;
    }
}
