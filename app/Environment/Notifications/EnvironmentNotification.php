<?php

namespace App\Environment\Notifications;

use App\Environment\Models\Environment;
use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class EnvironmentNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    public function __construct(
        protected Environment $environment,
    ) {}

    public function forOrganization(): Organization
    {
        return $this->environment->owningOrganization();
    }

    public function via(object $notifiable): array|string
    {
        return ['mail', 'slack'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->line($this->messageLine())
            ->line('You are receiving this alert because you are an administrator of this organization.');
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    abstract protected function subject(): string;

    abstract protected function messageLine(): string;
}
