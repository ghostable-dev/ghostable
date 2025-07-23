<?php

namespace App\Team\Actions;

use App\Team\Events\TeamSettingsChanged;
use App\Team\Models\Team;

class UpdateTeamName
{
    public function handle(Team $team, string $name): Team
    {
        $team->update(['name' => $name]);
        
        TeamSettingsChanged::dispatch($team);

        return $team;
    }
}
