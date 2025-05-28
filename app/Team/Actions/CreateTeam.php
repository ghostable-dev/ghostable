<?php

namespace App\Team\Actions;

use App\Account\Models\User;
use App\Team\Models\Team;

class CreateTeam
{
    public static function handle(string $name, User $owner, bool $personal = false): Team
    {
        $team = new Team;
        $team->name = $name;
        $team->owner()->associate($owner);
        $team->is_personal = $personal;
        $team->save();

        $owner->assignAsAdmin($team);

        return $team;
    }
}
