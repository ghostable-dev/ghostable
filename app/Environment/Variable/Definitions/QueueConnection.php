<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class QueueConnection extends VariableDefinition
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

    public function group(): VariableGroup
    {
        return VariableGroup::Queue;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: $this->suggestedValues())),
        ];
    }
}
