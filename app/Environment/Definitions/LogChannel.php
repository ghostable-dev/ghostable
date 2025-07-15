<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class LogChannel extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'LOG_CHANNEL';
    }

    public function description(): ?string
    {
        return 'The logging channel to use (e.g., stack, single, daily, etc.).';
    }

    public function suggestedValues(): array
    {
        return ['stack', 'single', 'daily', 'slack'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Logging;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
