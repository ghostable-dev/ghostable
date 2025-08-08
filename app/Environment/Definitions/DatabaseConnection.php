<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabaseConnection extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_CONNECTION';
    }

    public function description(): ?string
    {
        return 'The type of database connection to use (e.g., mysql, pgsql, sqlite).';
    }

    public function suggestedValues(): array
    {
        return ['mysql', 'pgsql', 'sqlite'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
}
