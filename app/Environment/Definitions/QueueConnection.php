<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class QueueConnection extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'QUEUE_CONNECTION';
    }

    public function rule(): string
    {
        return 'required|in:sync,database,redis,sqs,beanstalkd,null';
    }

    public function description(): ?string
    {
        return 'The queue connection your application should use.';
    }

    public function suggestedValues(): array
    {
        return ['sync', 'database', 'redis', 'sqs', 'beanstalkd', 'null'];
    }

    public function inputType(): ?string
    {
        return 'select';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Queue;
    }
}