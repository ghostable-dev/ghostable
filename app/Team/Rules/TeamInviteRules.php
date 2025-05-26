<?php

namespace App\Team\Rules;

use App\Account\Rules\UserRules;
use App\Team\Models\Team;

class TeamInviteRules
{
    public static function rules(Team $team): array
    {
        return [
            'email' => array_merge(
                UserRules::emailRules(), 
                [new UniqueTeamInvite($team)],
                [new UniqueEmailForTeam($team)],
            )
        ];
    }
}