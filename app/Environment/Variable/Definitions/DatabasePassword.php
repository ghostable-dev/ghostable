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

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The password used to authenticate with your database.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
    // @codeCoverageIgnoreEnd
}
