<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class LogLevel extends VariableDefinition
{
    public function key(): string
    {
        return 'LOG_LEVEL';
    }

    public function description(): ?string
    {
        return 'The minimum log level to record.';
    }

    public function suggestedValues(): array
    {
        return ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
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
