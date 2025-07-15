<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class SessionDriver extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'SESSION_DRIVER';
    }

    public function description(): ?string
    {
        return 'The session driver used to handle user sessions.';
    }

    public function suggestedValues(): array
    {
        return ['file', 'cookie', 'database', 'redis', 'memcached', 'dynamodb', 'array'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
