<?php

namespace App\Project\Listeners;

use App\Core\Actions\GetNotifiableOrganizationUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Organization\Models\Organization;
use App\Project\Enums\ProjectNotification;
use App\Project\Events\ProjectSettingsChanged;
use App\Project\Notifications\ProjectSettingsChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendProjectSettingsChangedNotification implements ShouldQueue
{
    public function handle(ProjectSettingsChanged $event): void
    {
        $organization = $event->project->owningOrganization();

        if (! $this->notificationEnabled($organization)) {
            return;
        }

        $recipients = app(GetNotifiableOrganizationUsers::class)->handle($organization);

        $notification = new ProjectSettingsChangedNotification($event->project);

        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }

    protected function notificationEnabled(Organization $organization): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $organization,
            key: ProjectNotification::PROJECT_SETTINGS_CHANGED->value
        );
    }
}
