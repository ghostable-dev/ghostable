<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class PusherAppKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'PUSHER_APP_KEY';
    }

    public function description(): ?string
    {
        return 'Your Pusher application key.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Pusher;
    }
    
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 128))
        ];
    }
}