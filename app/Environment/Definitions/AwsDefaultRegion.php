<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\EnumKeyRule;

class AwsDefaultRegion extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_DEFAULT_REGION';
    }

    public function description(): ?string
    {
        return 'The default AWS region to use for S3 and other services.';
    }

    public function suggestedValues(): array
    {
        return ['us-east-1', 'us-west-2', 'eu-central-1'];
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }

    public function ruleProviders(): array
    {
        return [
            new EnumKeyRule(new RuleParameters(allowedValues: [
                'us-east-1',
                'us-west-1',
                'us-west-2',
                'eu-west-1',
                'eu-central-1',
                'ap-southeast-1',
                'ap-northeast-1',
            ])),
        ];
    }
}
