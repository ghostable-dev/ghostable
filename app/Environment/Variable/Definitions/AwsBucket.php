<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Rules\StringKeyRule;
use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AwsBucket extends VariableDefinition
{
    public function key(): string
    {
        return 'AWS_BUCKET';
    }

    public function description(): ?string
    {
        return 'The name of the default S3 bucket used by your application.';
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
