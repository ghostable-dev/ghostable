<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailMailer extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_MAILER';
    }

    public function description(): ?string
    {
        return 'The default mailer your application will use.';
    }

    public function suggestedValues(): array
    {
        return ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array'];
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Mail;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
