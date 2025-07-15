<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class AppKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_KEY';
    }

    public function description(): ?string
    {
        return 'The base64-encoded encryption key used by Laravel.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new StringKeyRule(new RuleParameters(min: 32)),
        ];
    }
}
