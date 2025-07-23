<?php

namespace App\Environment\Listeners;

use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Environment\Events\EnvironmentCreated;
use App\Environment\Events\EnvironmentDeleted;
use App\Environment\Notifications\EnvironmentNotification;
use App\Project\Enums\ProjectNotification;
use App\Project\Notifications\EnvironmentCreatedNotification;
use App\Project\Notifications\EnvironmentDeletedNotification;
use App\Team\Models\Team;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendEnvironmentActivityNotification implements ShouldQueue
{
    public function handle(EnvironmentCreated|EnvironmentDeleted $event): void
    {
        $team = $event->environment->owningTeam();
        
        if (!$this->notificationEnabled($team)) {
            return;
        }

        $recipients = app(GetNotifiableTeamUsers::class)->handle($team);
        
        $notification = $this->getNotification($event);
        
        foreach ($recipients as $recipient) {
            Notification::send($recipient, $notification);
        }
    }
    
    protected function notificationEnabled(Team $team): bool
    {
        return app(IsNotificationEnabled::class)->handle(
            model: $team, 
            key: ProjectNotification::ENVIRONMENT_ACTIVITY->value
        );
    }
    
    protected function getNotification(
        EnvironmentCreated|EnvironmentDeleted $event
    ): EnvironmentNotification
    {
        if (is_a($event, EnvironmentCreated::class)) {
            return new EnvironmentCreatedNotification($event->environment);
        }
        
        if (is_a($event, EnvironmentDeleted::class)) {
            return new EnvironmentDeletedNotification($event->environment);
        }
        
        throw new Exception("Unknown environment event.");   
    }
}
