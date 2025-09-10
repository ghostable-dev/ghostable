<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class PusherAppKey extends VariableDefinition
{
    public function key(): string
    {
        return 'PUSHER_APP_KEY';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'Your Pusher application key.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Pusher;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 128)),
        ];
    }
}
