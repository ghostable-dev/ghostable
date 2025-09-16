<?php

namespace App\Organization\Notifications;

use App\Integration\Integrations\Slack\SlackNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class MembershipActivityNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    public function via(object $notifiable): array
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
        return 'mail.organization.membership-activity';
    }

    protected function mailViewData(): array
    {
        return [
            'title' => $this->subject(),
            'organization' => $this->forOrganization(),
            'message' => $this->messageLine(),
        ];
    }

    abstract protected function subject(): string;

    abstract protected function messageLine(): string;
}
