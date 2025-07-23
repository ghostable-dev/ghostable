<?php

namespace App\Project\Listeners;

use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Project\Enums\ProjectNotification;
use App\Project\Events\ProjectSettingsChanged;
use App\Project\Notifications\ProjectSettingsChangedNotification;
use App\Team\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendProjectSettingsChangedNotification implements ShouldQueue
{
    public function handle(ProjectSettingsChanged $event): void
    {
        $team = $event->project->owningTeam();

        if (! $this->notificationEnabled($team)) {
            return;
        }

        $recipients = app(GetNotifiableTeamUsers::class)->handle($team);

        $notification = new ProjectSettingsChangedNotification($event->project);

        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }

    protected function notificationEnabled(Team $team): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $team,
            key: ProjectNotification::PROJECT_SETTINGS_CHANGED->value
        );
    }
}
