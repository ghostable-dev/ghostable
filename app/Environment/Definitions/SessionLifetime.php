<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class SessionLifetime extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'SESSION_LIFETIME';
    }

    public function rule(): string
    {
        return 'required|integer|min:1|max:10080'; // 1 minute to 1 week
    }

    public function description(): ?string
    {
        return 'The number of minutes a session remains active before expiring.';
    }

    public function suggestedValues(): array
    {
        return ['15', '60', '120', '1440'];
    }

    public function inputType(): ?string
    {
        return 'number';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}