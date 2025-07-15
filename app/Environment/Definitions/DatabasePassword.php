<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabasePassword extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_PASSWORD';
    }

    public function description(): ?string
    {
        return 'The password used to authenticate with your database.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
}
