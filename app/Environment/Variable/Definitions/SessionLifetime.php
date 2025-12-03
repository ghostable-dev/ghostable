<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class SessionLifetime extends VariableDefinition
{
    public function key(): string
    {
        return 'SESSION_LIFETIME';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The number of minutes a session remains active before expiring.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['15', '60', '120', '1440'];
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
