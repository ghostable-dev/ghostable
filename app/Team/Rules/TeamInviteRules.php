<?php

namespace App\Team\Rules;

use App\Account\Rules\UserRules;
use App\Team\Models\Team;

class TeamInviteRules
{
    public static function createRules(Team $team): array
    {
        return [
            'email' => array_merge(
                UserRules::emailRules(),
                [new UniqueTeamInvite($team)],
                [new UniqueEmailForTeam($team)],
            ),
            'role' => [
                'required',
                new ValidTeamRole,
            ],
        ];
    }
}
