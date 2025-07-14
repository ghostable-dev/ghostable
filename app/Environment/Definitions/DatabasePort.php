<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;

class DatabasePort extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_PORT';
    }

    public function description(): ?string
    {
        return 'Port used to connect to the database.';
    }

    public function suggestedValues(): array
    {
        return ['3306', '5432'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
    
    public function ruleProviders(): array
    {
        return [
            new IntegerKeyRule(new RuleParameters(min: 1024, max: 65535))
        ];
    }
}