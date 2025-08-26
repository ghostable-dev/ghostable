<?php

namespace App\Environment\Listeners;

use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Notifications\EnvironmentCreatedNotification;
use App\Environment\Notifications\EnvironmentDeletedNotification;
use App\Environment\Notifications\EnvironmentNotification;
use App\Organization\Models\Organization;
use App\Project\Enums\ProjectNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendEnvironmentActivityNotification implements ShouldQueue
{
    public function handle(EnvironmentCreated|EnvironmentDeleted $event): void
    {
        $organization = $event->environment->owningOrganization();

        if (! $this->notificationEnabled($organization)) {
            return;
        }

        $recipients = app(GetNotifiableOrganizationUsers::class)->handle($organization);

        $notification = $this->getNotification($event);

        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }

    protected function notificationEnabled(Organization $organization): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $organization,
            key: ProjectNotification::ENVIRONMENT_ACTIVITY->value
        );
    }

    protected function getNotification(
        EnvironmentCreated|EnvironmentDeleted $event
    ): EnvironmentNotification {
        if (is_a($event, EnvironmentCreated::class)) {
            return new EnvironmentCreatedNotification($event->environment);
        }

        if (is_a($event, EnvironmentDeleted::class)) {
            return new EnvironmentDeletedNotification($event->environment);
        }

        throw new Exception('Unknown environment event.');
    }
}
