<?php

namespace App\Project\Listeners;

use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Organization\Enums\OrganizationNotification;
use App\Organization\Models\Organization;
use App\Project\Events\ProjectCreated;
use App\Project\Events\ProjectDeleted;
use App\Project\Notifications\ProjectCreatedNotification;
use App\Project\Notifications\ProjectDeletedNotification;
use App\Project\Notifications\ProjectNotification;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendProjectActivityNotification implements ShouldQueue
{
    public function handle(ProjectCreated|ProjectDeleted $event): void
    {
        $organization = $event->project->owningOrganization();

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
            key: OrganizationNotification::PROJECT_ACTIVITY->value
        );
    }

    protected function getNotification(
        ProjectCreated|ProjectDeleted $event
    ): ProjectNotification {
        if (is_a($event, ProjectCreated::class)) {
            return new ProjectCreatedNotification($event->project);
        }

        if (is_a($event, ProjectDeleted::class)) {
            return new ProjectDeletedNotification($event->project);
        }

        throw new Exception('Unknown project event.');
    }
}
