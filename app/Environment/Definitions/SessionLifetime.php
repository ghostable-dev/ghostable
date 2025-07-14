<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;

class SessionLifetime extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'SESSION_LIFETIME';
    }

    public function description(): ?string
    {
        return 'The number of minutes a session remains active before expiring.';
    }

    public function suggestedValues(): array
    {
        return ['15', '60', '120', '1440'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
    
    public function ruleProviders(): array
    {
        return [
            
            new IntegerKeyRule(new RuleParameters(
                min: 1, // 1 min
                max: 10080 // 1 week
            ))
        ];
    }
}