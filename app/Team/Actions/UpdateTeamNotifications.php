<?php

namespace App\Team\Actions;

use App\Team\Entities\TeamNotificationsData;
use App\Team\Models\Team;

class UpdateTeamNotifications
{
    public function handle(Team $team, TeamNotificationsData $data): Team
    {
        $team->update(['notifications' => $data]);

        return $team;
    }
}
