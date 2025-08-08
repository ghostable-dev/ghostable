<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabasePassword extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_PASSWORD';
    }

    public function description(): ?string
    {
        return 'The password used to authenticate with your database.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
}
