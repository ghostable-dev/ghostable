<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AppName extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_NAME';
    }

    public function rule(): string
    {
        return 'required|string|max:255';
    }

    public function description(): ?string
    {
        return 'The name of your application.';
    }

    public function inputType(): ?string
    {
        return 'text';
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}