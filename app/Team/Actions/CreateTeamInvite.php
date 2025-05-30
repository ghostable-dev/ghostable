<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Enums\TeamRole;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;

class CreateTeamInvite
{
    public static function handle(
        Team $team,
        User $user,
        string $email,
        TeamRole $role
    ): TeamInvite {
        $invite = new TeamInvite;
        $invite->team()->associate($team);
        $invite->user()->associate($user);
        $invite->email = $email;
        $invite->role = $role;
        $invite->save();

        return $invite;
    }
}
