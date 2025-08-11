<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppKey extends VariableDefinition
{
    public function key(): string
    {
        return 'APP_KEY';
    }

    public function description(): ?string
    {
        return 'The base64-encoded encryption key used by Laravel.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new StringKeyRule(new RuleParameters(min: 32)),
        ];
    }
}
