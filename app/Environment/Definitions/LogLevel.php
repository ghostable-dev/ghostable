<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class LogLevel extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'LOG_LEVEL';
    }

    public function rule(): string
    {
        return 'required|in:debug,info,notice,warning,error,critical,alert,emergency';
    }

    public function description(): ?string
    {
        return 'The minimum log level to record.';
    }

    public function suggestedValues(): array
    {
        return ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Logging;
    }
}