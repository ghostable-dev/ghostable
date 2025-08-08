<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class LogChannel extends VariableDefinition
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

    public function group(): VariableGroup
    {
        return VariableGroup::Logging;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
