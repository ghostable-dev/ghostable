<?php

namespace App\Environment\Variable\Listeners;

use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Enums\EnvironmentNotification;
use App\Environment\Variable\Events\VariableUpdated;
use App\Environment\Variable\Notifications\VariableUpdatedNotification;
use App\Team\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendVariableUpdatedNotification implements ShouldQueue
{
    public function handle(VariableUpdated $event): void
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
