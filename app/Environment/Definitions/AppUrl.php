<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Rules\UrlKeyRule;

class AppUrl extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_URL';
    }

    public function description(): ?string
    {
        return 'The base URL of your application (e.g., https://example.com).';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            $this->requiredProvider(),
            new UrlKeyRule,
        ];
    }
}
