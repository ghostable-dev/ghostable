<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Rules\BooleanKeyRule;

class AppDebug extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_DEBUG';
    }

    public function description(): ?string
    {
        return 'Whether to show detailed error messages. Should be false in production.';
    }

    public function suggestedValues(): array
    {
        return ['true', 'false'];
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
    
    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new BooleanKeyRule()
        ];
    }
}