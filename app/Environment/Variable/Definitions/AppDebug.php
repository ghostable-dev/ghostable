<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AppDebug extends VariableDefinition
{
    public function key(): string
    {
        return 'APP_DEBUG';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'Whether to show detailed error messages. Should be false in production.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['true', 'false'];
    }

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
