<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AppKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_KEY';
    }

    public function rule(): string
    {
        return 'required|string|min:32';
    }

    public function description(): ?string
    {
        return 'The base64-encoded encryption key used by Laravel.';
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