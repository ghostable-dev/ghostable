<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class AwsSecretAccessKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_SECRET_ACCESS_KEY';
    }

    public function description(): ?string
    {
        return 'Your AWS secret access key, used in combination with the access key ID.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }
    
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255))
        ];
    }
}