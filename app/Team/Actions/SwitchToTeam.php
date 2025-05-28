<?php

namespace App\Team\Actions;

use App\Team\Models\Team;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class SwitchToTeam
{
    public static function handle(Team $team): void
    {
        if (! $team->users->contains(Auth::user())) {
            throw new AuthorizationException('You are not a member of this team.');
        }

        session()->put('current_team_id', $team->id);
    }
}
