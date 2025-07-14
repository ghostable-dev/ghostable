<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class MailEncryption extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_ENCRYPTION';
    }

    public function description(): ?string
    {
        return 'The encryption protocol to use when sending mail.';
    }

    public function suggestedValues(): array
    {
        return ['tls', 'ssl', 'null'];
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