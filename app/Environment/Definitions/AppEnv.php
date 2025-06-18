<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AppEnv extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_ENV';
    }

    public function rule(): string
    {
        return 'required|in:local,production,staging,testing';
    }

    public function description(): ?string
    {
        return 'The environment your application is running in.';
    }

    public function suggestedValues(): array
    {
        return ['local', 'production', 'staging', 'testing'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}