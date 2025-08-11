<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailEncryption extends VariableDefinition
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
