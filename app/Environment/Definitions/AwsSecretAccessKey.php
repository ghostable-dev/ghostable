<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AwsSecretAccessKey extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'AWS_SECRET_ACCESS_KEY';
    }

    public function rule(): string
    {
        return 'required|string|max:255';
    }

    public function description(): ?string
    {
        return 'Your AWS secret access key, used in combination with the access key ID.';
    }

    public function inputType(): ?string
    {
        return 'password';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Aws;
    }
}