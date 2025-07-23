<?php

namespace App\Team\Concerns;

use App\Account\Models\User;
use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Team\Models\Team;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as Sender;

trait SendsTeamNotifications
{
    /**
     * Is the team notification (determined by key)
     * enabled for the given team
     */
    protected function isNotificationEnabled(
        Team $team,
        string $key
    ): bool {
        return app(IsNotificationEnabled::class)->handle($team, $key);
    }

    /**
     * Get the team notifiable recipients.
     */
    protected function getTeamRecipients(Team $team): Collection
    {
        return app(GetNotifiableTeamUsers::class)->handle($team);
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
