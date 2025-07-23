<?php

namespace App\Project\Notifications;

use App\Account\Models\User;
use App\Project\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectActivityNotification extends Notification
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Project $project,
        protected User $actor,
        protected string $action, // 'created' or 'deleted'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->messageLine())
            ->line('You are receiving this alert because you are an administrator of this team.');
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    protected function subject(): string
    {
        return match ($this->action) {
            'created' => "{$this->actor->name} created a new project",
            'deleted' => "{$this->actor->name} deleted a project",
            default => 'Project activity in your team',
        };
    }

    protected function messageLine(): string
    {
        return match ($this->action) {
            'created' => "{$this->actor->name} created the project \"{$this->project->name}\" in the team \"{$this->project->team->name}\".",
            'deleted' => "{$this->actor->name} deleted the project \"{$this->project->name}\" from the team \"{$this->project->team->name}\".",
            default => 'A project activity occurred.',
        };
    }
}