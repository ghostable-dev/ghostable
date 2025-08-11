<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AwsSecretAccessKey extends VariableDefinition
{
    public function key(): string
    {
        return 'AWS_SECRET_ACCESS_KEY';
    }

    public function description(): ?string
    {
        return 'Your AWS secret access key, used in combination with the access key ID.';
    }

    public function group(): VariableGroup
    {
        return VariableGroup::Aws;
    }

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
