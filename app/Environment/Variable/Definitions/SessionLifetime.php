<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\IntegerKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class SessionLifetime extends VariableDefinition
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

    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [

            new IntegerKeyRule(new RuleParameters(
                min: 1, // 1 min
                max: 10080 // 1 week
            )),
        ];
    }
}
