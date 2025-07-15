<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class QueueConnection extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'QUEUE_CONNECTION';
    }

    public function description(): ?string
    {
        return 'The queue connection your application should use.';
    }

    public function suggestedValues(): array
    {
        return ['sync', 'database', 'redis', 'sqs', 'beanstalkd', 'null'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Queue;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
