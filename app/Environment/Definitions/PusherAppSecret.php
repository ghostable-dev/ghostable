<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class PusherAppSecret extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'PUSHER_APP_SECRET';
    }

    public function description(): ?string
    {
        return 'Your Pusher application secret.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Pusher;
    }
    
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255))
        ];
    }
}