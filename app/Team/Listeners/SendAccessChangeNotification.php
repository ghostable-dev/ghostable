<?php

namespace App\Team\Listeners;

use App\Team\Concerns\SendsTeamNotifications;
use App\Team\Enums\TeamNotification;
use App\Team\Events\MemberRoleChanged;
use App\Team\Notifications\AccessChangeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAccessChangeNotification implements ShouldQueue
{
    use SendsTeamNotifications;
    
    public function handle(MemberRoleChanged $event): void
    {
        $team = $event->team;
        
        $eventKey = TeamNotification::MEMBERSHIP_ACTIVITY->value;
        if (!$this->isNotificationEnabled($team, $eventKey)) {
            return;
        }

        $notification = new AccessChangeNotification($team, $event->user);
        
        foreach ($this->getTeamRecipients($team) as $recipient) {
            $this->sendNotification($recipient, $notification);
        }
    }
}
