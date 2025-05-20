<?php

namespace App\Account\Actions;

use App\Account\Models\Team;
use App\Account\Models\User;

class CreateTeam
{
    public static function handle(string $name, User $owner): Team
    {
        $team = new Team();
        $team->name = $name;
        $team->owner()->associate($owner);
        $team->save();
        
        $team->users()->attach($owner->id, ['role' => 'owner']);
        
        return $team;
    }
}
