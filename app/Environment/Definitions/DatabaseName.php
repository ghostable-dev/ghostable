<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class DatabaseName extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'DB_DATABASE';
    }
    
    public function description(): ?string
    {
        return 'The name of your application\'s database.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Database;
    }
    
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255))
        ];
    }
}