<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AppDebug extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_DEBUG';
    }

    public function rule(): string
    {
        return 'required|in:true,false,TRUE,FALSE';
    }

    public function description(): ?string
    {
        return 'Whether to show detailed error messages. Should be false in production.';
    }

    public function suggestedValues(): array
    {
        return ['true', 'false'];
    }

    public function inputType(): ?string
    {
        return 'boolean';
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}