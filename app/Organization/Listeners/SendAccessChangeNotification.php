<?php

namespace App\Organization\Listeners;

use App\Organization\Concerns\SendsOrganizationNotifications;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Events\MemberRoleChanged;
use App\Organization\Notifications\AccessChangeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAccessChangeNotification implements ShouldQueue
{
    use SendsOrganizationNotifications;

    public function handle(MemberRoleChanged $event): void
    {
        $organization = $event->organization;

        $eventKey = OrganizationNotification::MEMBERSHIP_ACTIVITY->value;
        if (! $this->isNotificationEnabled($organization, $eventKey)) {
            return;
        }

        $notification = new AccessChangeNotification($organization, $event->user);

        foreach ($this->getOrganizationRecipients($organization) as $recipient) {
            $this->sendNotification($recipient, $notification);
        }
    }
}
