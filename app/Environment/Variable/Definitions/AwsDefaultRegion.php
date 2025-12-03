<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AwsDefaultRegion extends VariableDefinition
{
    public function key(): string
    {
        return 'AWS_DEFAULT_REGION';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The default AWS region to use for S3 and other services.';
    }
    // @codeCoverageIgnoreEnd

    public function suggestedValues(): array
    {
        return ['us-east-1', 'us-west-2', 'eu-central-1'];
    }

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Aws;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [];
    }
}
