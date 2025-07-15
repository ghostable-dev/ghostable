<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;

class MailPort extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'MAIL_PORT';
    }

    public function description(): ?string
    {
        return 'The port your mail server uses.';
    }

    public function suggestedValues(): array
    {
        return ['25', '465', '587'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Mail;
    }

    public function ruleProviders(): array
    {
        return [
            new IntegerKeyRule(new RuleParameters(min: 1, max: 65535)),
        ];
    }
}
