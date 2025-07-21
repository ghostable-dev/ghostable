<?php

namespace App\Team\Actions;

use App\Team\Models\Team;
use App\Team\Notifications\TeamNotificationsData;

class UpdateTeamNotifications
{
    public function handle(Team $team, TeamNotificationsData $data): Team
    {
        $team->update(['notifications' => $data]);

        return $team;
    }
}
