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
            ->view('mail.organization.settings-changed', [
                'title' => $this->title(),
                'organization' => $this->organization,
            ]);
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: %s settings updated',
            $this->organization->name,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            'Settings for the "%s" organization were updated on Ghostable.',
            $this->organization->name,
        );
    }

    protected function title(): string
    {
        return 'Organization updated';
    }
}
