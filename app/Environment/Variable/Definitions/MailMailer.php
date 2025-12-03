<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailMailer extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_MAILER';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The default mailer your application will use.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Mail;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
