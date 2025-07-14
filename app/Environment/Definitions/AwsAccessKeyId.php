<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class AwsAccessKeyId extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_ACCESS_KEY_ID';
    }

    public function description(): ?string
    {
        return 'Your AWS access key ID used for authenticating AWS SDK requests.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }
    
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 128))
        ];
    }
}