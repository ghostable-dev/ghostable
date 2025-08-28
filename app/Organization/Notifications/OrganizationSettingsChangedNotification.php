<?php

namespace App\Organization\Notifications;

use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrganizationSettingsChangedNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    public function __construct(protected Organization $organization) {}

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
        return 'Organization settings changed';
    }

    protected function messageLine(): string
    {
        return "Organization settings for the \"{$this->organization->name}\" organization has been updated.";
    }
}
