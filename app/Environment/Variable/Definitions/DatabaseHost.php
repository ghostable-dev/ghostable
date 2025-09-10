<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabaseHost extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_HOST';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The hostname or IP address of your database server.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
    // @codeCoverageIgnoreEnd
}
