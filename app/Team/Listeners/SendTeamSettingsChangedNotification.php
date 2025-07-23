<?php

namespace App\Team\Listeners;

use App\Team\Concerns\SendsTeamNotifications;
use App\Team\Enums\TeamNotification;
use App\Team\Events\TeamSettingsChanged;
use App\Team\Notifications\TeamSettingsChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTeamSettingsChangedNotification implements ShouldQueue
{
    use SendsTeamNotifications;

    public function handle(TeamSettingsChanged $event): void
    {
        $team = $event->team;

        $eventKey = TeamNotification::TEAM_SETTINGS_CHANGED->value;
        if (! $this->isNotificationEnabled($team, $eventKey)) {
            return;
        }

        $notification = new TeamSettingsChangedNotification($team);

        foreach ($this->getTeamRecipients($team) as $recipient) {
            $this->sendNotification($recipient, $notification);
        }
    }
}
