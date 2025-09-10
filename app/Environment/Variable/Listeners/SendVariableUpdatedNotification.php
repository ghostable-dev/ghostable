<?php

namespace App\Environment\Variable\Listeners;

use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Enums\EnvironmentNotification;
use App\Environment\Variable\Events\VariableUpdated;
use App\Environment\Variable\Notifications\VariableUpdatedNotification;
use App\Organization\Models\Organization;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendVariableUpdatedNotification implements ShouldQueue
{
    public function handle(VariableUpdated $event): void
    {
        $organization = $event->variable->environment->owningOrganization();

        if (! $this->notificationEnabled($organization)) {
            return;
        }

        $recipients = app(GetNotifiableOrganizationUsers::class)->handle($organization);

        $notification = new VariableUpdatedNotification($event->variable);

        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }

    protected function notificationEnabled(Organization $organization): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $organization,
            key: EnvironmentNotification::VARIABLE_UPDATED->value
        );
    }
}
