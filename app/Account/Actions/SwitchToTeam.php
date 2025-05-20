<?php

namespace App\Account\Actions;

use App\Account\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;

class SwitchToTeam
{
    public static function handle(Team $team): void
    {
        if (!$team->users->contains(auth()->user())) {
            throw new AuthorizationException('You are not a member of this team.');
        }
        
        session()->put('current_team_id', $team->id);
    }
}
