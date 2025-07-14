<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class MailMailer extends EnvironmentVariableDefinition
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

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }
    
    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues()))
        ];
    }
}