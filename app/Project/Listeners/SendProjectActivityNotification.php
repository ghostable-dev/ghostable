<?php

namespace App\Project\Listeners;

use App\Core\Actions\GetNotifiableTeamUsers;
use App\Core\Actions\IsNotificationEnabled;
use App\Project\Events\ProjectCreated;
use App\Project\Events\ProjectDeleted;
use App\Project\Notifications\ProjectCreatedNotification;
use App\Project\Notifications\ProjectDeletedNotification;
use App\Project\Notifications\ProjectNotification;
use App\Team\Enums\TeamNotification;
use App\Team\Models\Team;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Exception;

class SendProjectActivityNotification implements ShouldQueue
{
    public function handle(ProjectCreated|ProjectDeleted $event): void
    {
        $team = $event->project->owningTeam();
        
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
            key: TeamNotification::PROJECT_ACTIVITY->value
        );
    }
    
    protected function getNotification(
        ProjectCreated|ProjectDeleted $event
    ): ProjectNotification
    {
        if (is_a($event, ProjectCreated::class)) {
            return new ProjectCreatedNotification($event->project);
        }
        
        if (is_a($event, ProjectDeleted::class)) {
            return new ProjectDeletedNotification($event->project);
        }
        
        throw new Exception("Unknown project event.");   
    }
}
