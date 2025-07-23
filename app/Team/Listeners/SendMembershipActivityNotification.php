<?php

namespace App\Team\Listeners;

use App\Team\Concerns\SendsTeamNotifications;
use App\Team\Enums\TeamNotification;
use App\Team\Events\InviteAccepted;
use App\Team\Events\InviteCreated;
use App\Team\Events\MemberRemoved;
use App\Team\Notifications\MemberInvitedNotification;
use App\Team\Notifications\MemberJoinedNotification;
use App\Team\Notifications\MemberRemovedNotification;
use App\Team\Notifications\MembershipActivityNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMembershipActivityNotification implements ShouldQueue
{
    use SendsTeamNotifications;

    public function handle(
        InviteCreated|InviteAccepted|MemberRemoved $event
    ): void {
        $team = $event->team;

        $eventKey = TeamNotification::MEMBERSHIP_ACTIVITY->value;
        if (! $this->isNotificationEnabled($team, $eventKey)) {
            return;
        }

        $notification = $this->getNotification($event);

        foreach ($this->getTeamRecipients($team) as $recipient) {
            $this->sendNotification($recipient, $notification);
        }
    }

    protected function getNotification(
        InviteCreated|InviteAccepted|MemberRemoved $event
    ): MembershipActivityNotification {
        if (is_a($event, InviteCreated::class)) {
            return new MemberInvitedNotification(
                invite: $event->invite
            );
        }

        if (is_a($event, InviteAccepted::class)) {
            return new MemberJoinedNotification(
                invite: $event->invite
            );
        }

        if (is_a($event, MemberRemoved::class)) {
            return new MemberRemovedNotification(
                team: $event->team,
                user: $event->user
            );
        }

        throw new Exception('Unknown team membership event.');
    }
}
