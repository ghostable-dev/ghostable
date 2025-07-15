<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;

class AwsBucket extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_BUCKET';
    }

    public function description(): ?string
    {
        return 'The name of the default S3 bucket used by your application.';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
