<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class DatabasePort extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_PORT';
    }

    public function rule(): string
    {
        return 'required|integer|min:1024|max:65535';
    }

    public function description(): ?string
    {
        return 'Port used to connect to the database.';
    }

    public function suggestedValues(): array
    {
        return ['3306', '5432'];
    }

    public function inputType(): ?string
    {
        return 'number';
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
}