<?php

namespace App\Project\Notifications;

use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use App\Project\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class ProjectNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Project $project,
    ) {}

    public function forOrganization(): Organization
    {
        return $this->project->organization;
    }

    public function via(object $notifiable): string|array
    {
        return ['mail', 'slack'];
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }
}
