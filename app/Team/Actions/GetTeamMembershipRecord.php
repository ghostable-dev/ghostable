<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Cache;
use App\Team\Actions\TeamMembershipAction;

class GetTeamMembershipRecord extends TeamMembershipAction
{
    public function handle(User $user, Team $team): ?Team
    {
        return Cache::rememberForever(
            $this->cacheKeyForMembershipRecord(team: $team, user: $user),
            fn () => $user->teams()->where('team_id', $team->id)->first()
        );
    }
}