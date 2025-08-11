<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class MailUsername extends VariableDefinition
{
    public function key(): string
    {
        return 'MAIL_USERNAME';
    }

    public function description(): ?string
    {
        return 'The username used to authenticate with your mail server.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Mail;
    }

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
