<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Rules\BooleanKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppDebug extends VariableDefinition
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

    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new BooleanKeyRule,
        ];
    }
}
