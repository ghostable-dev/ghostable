<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class DatabaseUsername extends VariableDefinition
{
    public function key(): string
    {
        return 'DB_USERNAME';
    }

    public function description(): ?string
    {
        return 'The username used to authenticate with your database.';
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
