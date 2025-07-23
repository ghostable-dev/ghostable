<?php

namespace App\Team\Notifications;

use App\Account\Models\User;
use App\Team\Models\Team;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessChangeNotification extends Notification
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Team $team,
        protected User $user,
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
        return "Member \"{$this->user->email}\" role was changed";
    }

    protected function messageLine(): string
    {
        return "Member \"{$this->user->email}\" role was changed in the \"{$this->team->name}\" team";
    }
}