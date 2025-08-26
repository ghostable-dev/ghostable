<?php

namespace App\Organization\Listeners;

use App\Organization\Concerns\SendsOrganizationNotifications;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Events\OrganizationSettingsChanged;
use App\Organization\Notifications\OrganizationSettingsChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrganizationSettingsChangedNotification implements ShouldQueue
{
    use SendsOrganizationNotifications;

    public function handle(OrganizationSettingsChanged $event): void
    {
        $organization = $event->organization;

        $eventKey = OrganizationNotification::ORGANIZATION_SETTINGS_CHANGED->value;
        if (! $this->isNotificationEnabled($organization, $eventKey)) {
            return;
        }

        $notification = new OrganizationSettingsChangedNotification($organization);

        foreach ($this->getOrganizationRecipients($organization) as $recipient) {
            $this->sendNotification($recipient, $notification);
        }
    }
}
