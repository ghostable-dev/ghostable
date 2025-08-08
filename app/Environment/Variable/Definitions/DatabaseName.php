<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabaseName extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_DATABASE';
    }

    public function description(): ?string
    {
        return 'The name of your application\'s database.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Database;
    }

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
