<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AwsAccessKeyId extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_ACCESS_KEY_ID';
    }

    public function rule(): string
    {
        return 'required|string|max:128';
    }

    public function description(): ?string
    {
        return 'Your AWS access key ID used for authenticating AWS SDK requests.';
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