<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class LogLevel extends EnvironmentVariableDefinition
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

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Logging;
    }
    
    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues()))
        ];
    }
}