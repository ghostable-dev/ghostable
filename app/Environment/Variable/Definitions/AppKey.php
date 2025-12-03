<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppKey extends VariableDefinition
{
    public function key(): string
    {
        return 'APP_KEY';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The base64-encoded encryption key used by Laravel.';
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
