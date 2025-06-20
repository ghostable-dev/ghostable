<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabaseName extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_DATABASE';
    }

    public function rule(): string
    {
        return 'string|max:255';
    }

    public function description(): ?string
    {
        return 'The name of your application\'s database.';
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
}