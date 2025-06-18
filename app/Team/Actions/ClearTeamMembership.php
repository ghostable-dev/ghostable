<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Cache;

class ClearTeamMembershipCache extends TeamMembershipAction
{
    public function handle(User $user, Team $team): void
    {
        Cache::forget($this->cacheKeyForMembership(team: $team, user: $user));
    }
}