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
            ->view($this->mailView(), $this->mailViewData());
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    protected function mailView(): string
    {
        return 'mail.environment.notification';
    }

    protected function mailViewData(): array
    {
        return [
            'title' => $this->title(),
            'environment' => $this->environment,
            'organization' => $this->forOrganization(),
            'message' => $this->messageLine(),
        ];
    }

    abstract protected function title(): string;

    abstract protected function subject(): string;

    abstract protected function messageLine(): string;
}
