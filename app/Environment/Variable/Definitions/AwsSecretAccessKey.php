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

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'Your AWS secret access key, used in combination with the access key ID.';
    }
    // @codeCoverageIgnoreEnd

    // @codeCoverageIgnoreStart
    public function group(): VariableGroup
    {
        return VariableGroup::Aws;
    }
    // @codeCoverageIgnoreEnd

    public function ruleProviders(): array
    {
        return [
            new StringKeyRule(new RuleParameters(max: 255)),
        ];
    }
}
