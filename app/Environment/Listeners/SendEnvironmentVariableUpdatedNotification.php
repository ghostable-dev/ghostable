<?php

namespace App\Environment\Listeners;

use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Enums\EnvironmentNotification;
use App\Environment\Events\EnvironmentVariableUpdated;
use App\Environment\Notifications\VariableUpdatedNotification;
use App\Team\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendEnvironmentVariableUpdatedNotification implements ShouldQueue
{
    public function handle(EnvironmentVariableUpdated $event): void
    {
        $team = $event->variable->environment->owningTeam();

        if (! $this->notificationEnabled($team)) {
            return;
        }

        $recipients = app(GetNotifiableTeamUsers::class)->handle($team);

        $notification = new VariableUpdatedNotification($event->variable);

        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }

    protected function notificationEnabled(Team $team): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $team,
            key: EnvironmentNotification::VARIABLE_UPDATED->value
        );
    }
}
