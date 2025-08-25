<?php

namespace App\Organization\Concerns;

use App\Account\Models\User;
use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as Sender;

trait SendsOrganizationNotifications
{
    /**
     * Is the organization notification (determined by key)
     * enabled for the given organization
     */
    protected function isNotificationEnabled(
        Organization $organization,
        string $key
    ): bool {
        return app(IsNotificationEnabled::class)->handle($organization, $key);
    }

    /**
     * Get the organization notifiable recipients.
     */
    protected function getOrganizationRecipients(Organization $organization): Collection
    {
        return app(GetNotifiableOrganizationUsers::class)->handle($organization);
    }

    /**
     * Send the notificiation to the given notifiable.
     */
    protected function sendNotification(
        User $recipient,
        Notification $notification
    ): void {
        Sender::send($recipient, $notification);
    }
}
