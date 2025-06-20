<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class LogChannel extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'LOG_CHANNEL';
    }

    public function rule(): string
    {
        return 'string';
    }

    public function description(): ?string
    {
        return 'The logging channel to use (e.g., stack, single, daily, etc.).';
    }

    public function suggestedValues(): array
    {
        return ['stack', 'single', 'daily', 'slack'];
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Logging;
    }
}