<?php

namespace App\Team\Actions;

use App\Account\Entities\Role;
use App\Team\Models\Team;
use App\Team\Models\TeamInvite;
use App\Account\Models\User;
use App\Team\Rules\TeamInviteRules;
use Illuminate\Support\Facades\Validator;

class CreateTeamInvite
{
    public static function handle(
        Team $team,
        User $user,
        string $email,
        Role $role
    ): TeamInvite
    {
        $validated = self::validate($team, compact('email'));
        $invite = new TeamInvite();
        $invite->team()->associate($team);
        $invite->user()->associate($user);
        $invite->email = $validated['email'];
        $invite->role = $role;
        $invite->save();
        return $invite;
    }
    
    protected static function validate(Team $team, array $input): array
    {
        return Validator::make(
            $input, 
            TeamInviteRules::rules($team)
        )->validate();
    }
}
