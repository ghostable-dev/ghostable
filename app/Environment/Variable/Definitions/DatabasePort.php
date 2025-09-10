<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabasePort extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_PORT';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'Port used to connect to the database.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['3306', '5432'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [
            new IntegerKeyRule(new RuleParameters(min: 1024, max: 65535)),
        ];
    }
}
