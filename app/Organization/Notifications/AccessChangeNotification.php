<?php

namespace App\Organization\Notifications;

use App\Account\Models\User;
use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessChangeNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Organization $organization,
        protected User $user,
    ) {}

    public function forOrganization(): Organization
    {
        return $this->organization;
    }

    public function via(object $notifiable): array|string
    {
        return ['mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->greeting($notifiable->greeting())
            ->line($this->messageLine())
            ->line('You are receiving this alert because you are an administrator of this organization.');
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
        return "Member \"{$this->user->email}\" role was changed in the \"{$this->organization->name}\" organization";
    }
}
