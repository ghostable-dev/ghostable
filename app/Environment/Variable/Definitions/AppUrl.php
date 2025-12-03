<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppUrl extends VariableDefinition
{
    public function key(): string
    {
        return 'APP_URL';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The base URL of your application (e.g., https://example.com).';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
