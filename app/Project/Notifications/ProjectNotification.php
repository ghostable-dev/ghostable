<?php

namespace App\Project\Notifications;

use App\Project\Models\Project;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class ProjectNotification extends Notification
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Project $project,
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

    abstract protected function subject(): string;

    abstract protected function messageLine(): string;
}