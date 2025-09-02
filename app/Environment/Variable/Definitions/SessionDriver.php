<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class SessionDriver extends VariableDefinition
{
    public function key(): string
    {
        return 'SESSION_DRIVER';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The session driver used to handle user sessions.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['file', 'cookie', 'database', 'redis', 'memcached', 'dynamodb', 'array'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::App;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
