<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;
use Illuminate\Support\Facades\Cache;

class CheckTeamMembership extends TeamMembershipAction
{
    public function handle(User $user, Team $team): bool
    {
        return Cache::rememberForever(
            $this->cacheKeyForMembership(team: $team, user: $user),
            fn () => $team->users()->where('user_id', $user->id)->exists()
        );
    }
}