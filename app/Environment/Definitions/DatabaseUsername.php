<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabaseUsername extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_USERNAME';
    }

    public function rule(): string
    {
        return 'string|max:255';
    }

    public function description(): ?string
    {
        return 'The username used to authenticate with your database.';
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