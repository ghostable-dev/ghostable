<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class QueueConnection extends VariableDefinition
{
    public function key(): string
    {
        return 'QUEUE_CONNECTION';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The queue connection your application should use.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['sync', 'database', 'redis', 'sqs', 'beanstalkd', 'null'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Queue;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
