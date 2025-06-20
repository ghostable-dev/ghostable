<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AwsBucket extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_BUCKET';
    }

    public function rule(): string
    {
        return 'string|max:255';
    }

    public function description(): ?string
    {
        return 'The name of the default S3 bucket used by your application.';
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }
}