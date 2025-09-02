<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabaseConnection extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_CONNECTION';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The type of database connection to use (e.g., mysql, pgsql, sqlite).';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['mysql', 'pgsql', 'sqlite'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
    // @codeCoverageIgnoreEnd
}
