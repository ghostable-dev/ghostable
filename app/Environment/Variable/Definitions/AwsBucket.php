<?php

namespace App\Environment\Variable\Definitions;

use App\Environment\Variable\Enums\VariableGroup;
use App\Environment\Variable\Registry\VariableDefinition;

class AwsBucket extends VariableDefinition
{
    public function key(): string
    {
        return 'AWS_BUCKET';
    }

    // @codeCoverageIgnoreStart
    public function description(): ?string
    {
        return 'The name of the default S3 bucket used by your application.';
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
        return [];
    }
}
