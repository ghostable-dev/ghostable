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
            ->view('mail.organization.access-changed', [
                'title' => $this->title(),
                'organization' => $this->organization,
                'user' => $this->user,
            ]);
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable update: Permissions updated for %s',
            $this->user->email,
        );
    }

    protected function messageLine(): string
    {
        return sprintf(
            'Permissions for "%s" were updated in the "%s" organization on Ghostable.',
            $this->user->email,
            $this->organization->name,
        );
    }

    protected function title(): string
    {
        return 'Role updated';
    }
}
