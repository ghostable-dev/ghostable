<?php

declare(strict_types=1);

namespace App\Organization\Notifications;

use App\Environment\Models\EnvironmentKeyReshareRequest;
use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class EnvironmentKeyReshareRequiredNotification extends Notification implements SlackNotifiable
{
    protected bool $unsubscribable = true;

    /**
     * @param  Collection<int, EnvironmentKeyReshareRequest>  $requests
     */
    public function __construct(
        private readonly Organization $organization,
        private readonly Collection $requests,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'slack'];
    }

    public function forOrganization(): Organization
    {
        return $this->organization;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject())
            ->view('mail.organization.key-reshare-required', $this->mailViewData());
    }

    public function toSlack(object $notifiable): string
    {
        return $this->messageLine();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mailViewData(): array
    {
        $requests = $this->requests
            ->sortBy(fn (EnvironmentKeyReshareRequest $request): string => (string) $request->created_at)
            ->values();

        /** @var EnvironmentKeyReshareRequest|null $first */
        $first = $requests->first();

        $dashboardUrl = route('organization.settings.members').'?tab=key-reshare-requests#key-reshare-requests';

        return [
            'title' => 'Environment key re-share required',
            'organization' => $this->organization,
            'requests' => $requests,
            'first_request' => $first,
            'dashboard_url' => $dashboardUrl,
            'cli_command' => $first ? sprintf('ghostable env reshare fulfill %s', (string) $first->getKey()) : null,
            'summary_line' => $this->messageLine(),
        ];
    }

    protected function subject(): string
    {
        return sprintf(
            'Ghostable action needed: re-share environment keys for %s',
            $this->organization->name,
        );
    }

    protected function messageLine(): string
    {
        $count = $this->requests->count();

        if ($count <= 0) {
            return sprintf(
                'An environment key re-share is required in "%s".',
                $this->organization->name,
            );
        }

        return sprintf(
            '%d pending environment key re-share request%s in "%s".',
            $count,
            $count === 1 ? '' : 's',
            $this->organization->name,
        );
    }
}
