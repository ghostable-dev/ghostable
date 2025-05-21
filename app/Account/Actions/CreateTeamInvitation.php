<?php

namespace App\Account\Actions;

use App\Account\Models\Team;
use App\Account\Models\TeamInvitation;
use Illuminate\Support\Str;

class CreateTeamInvitation
{
    public static function handle(Team $team, string $email, string $role = 'member'): TeamInvitation
    {
        $invite = new TeamInvitation();
        $invite->email = $email;
        $invite->role = $role;
        $invite->token = Str::uuid();
        $invite->team()->associate($team);
        $invite->expires_at = now()->addDays(7);
        $invite->save();

        return $invite;
    }
}
