<?php

namespace App\Organization\Listeners;

use App\Organization\Concerns\SendsOrganizationNotifications;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Events\InviteAccepted;
use App\Organization\Events\InviteCreated;
use App\Organization\Events\MemberRemoved;
use App\Organization\Notifications\MemberInvitedNotification;
use App\Organization\Notifications\MemberJoinedNotification;
use App\Organization\Notifications\MemberRemovedNotification;
use App\Organization\Notifications\MembershipActivityNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendMembershipActivityNotification implements ShouldQueue
{
    use SendsOrganizationNotifications;

    public function handle(
        InviteCreated|InviteAccepted|MemberRemoved $event
    ): void {
        $organization = $event->organization;

        $eventKey = OrganizationNotification::MEMBERSHIP_ACTIVITY->value;
        if (! $this->isNotificationEnabled($organization, $eventKey)) {
            return;
        }

        $notification = $this->getNotification($event);

        foreach ($this->getOrganizationRecipients($organization) as $recipient) {
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
                organization: $event->organization,
                user: $event->user
            );
        }

        throw new Exception('Unknown organization membership event.');
    }
}
