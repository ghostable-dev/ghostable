<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabaseHost extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_HOST';
    }

    public function description(): ?string
    {
        return 'The hostname or IP address of your database server.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
}
