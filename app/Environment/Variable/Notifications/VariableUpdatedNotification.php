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
            ->subject('Variable Updated')
            ->view('mail.environment.variable-updated', [
                'title' => 'Variable updated',
                'variable' => $this->variable,
                'environment' => $this->variable->environment,
                'organization' => $this->forOrganization(),
            ]);
    }

    public function toSlack(object $notifiable): array|string
    {
        return "Variable '{$this->variable->key}' was updated in environment '{$this->variable->environment->name}' of the \"{$this->variable->environment->project->name}\" project on the \"{$this->forOrganization()->name}\" organization.";
    }
}
