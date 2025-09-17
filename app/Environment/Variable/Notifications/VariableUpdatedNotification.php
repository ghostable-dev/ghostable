<?php

namespace App\Environment\Variable\Notifications;

use App\Core\Notifications\BaseNotification;
use App\Environment\Variable\Models\EnvironmentVariable;
use App\Integration\Integrations\Slack\SlackNotifiable;
use App\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;

class VariableUpdatedNotification extends BaseNotification implements SlackNotifiable
{
    public function __construct(protected EnvironmentVariable $variable) {}

    public function forOrganization(): Organization
    {
        return $this->variable->environment->owningOrganization();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf(
                'Ghostable update: Variable "%s" updated',
                $this->variable->key,
            ))
            ->view('mail.environment.variable-updated', [
                'title' => 'Variable updated',
                'variable' => $this->variable,
                'environment' => $this->variable->environment,
                'organization' => $this->forOrganization(),
            ]);
    }

    public function toSlack(object $notifiable): array|string
    {
        return sprintf(
            'The "%s" variable was updated in the "%s" environment of the "%s" project in the "%s" organization on Ghostable.',
            $this->variable->key,
            $this->variable->environment->name,
            $this->variable->environment->project->name,
            $this->forOrganization()->name,
        );
    }
}
