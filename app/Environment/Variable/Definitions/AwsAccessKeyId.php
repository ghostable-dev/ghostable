<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AwsAccessKeyId extends VariableDefinition
{
    public function key(): string
    {
        return 'AWS_ACCESS_KEY_ID';
    }

    public function description(): ?string
    {
        return 'Your AWS access key ID used for authenticating AWS SDK requests.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Aws;
    }

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 128)),
        ];
    }
}
