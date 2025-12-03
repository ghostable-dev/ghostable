<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabaseUsername extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_USERNAME';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The username used to authenticate with your database.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
