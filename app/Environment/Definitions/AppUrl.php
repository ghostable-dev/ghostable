<?php

namespace App\Environment\Definitions;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Registry\EnvironmentVariableDefinition;

class AppUrl extends EnvironmentVariableDefinition
{
    public function key(): string
    {
        return 'APP_URL';
    }

    public function rule(): string
    {
        return 'required|url';
    }

    public function description(): ?string
    {
        return 'The base URL of your application (e.g., https://example.com).';
    }

    public function inputType(): ?string
    {
        return 'text';
    }

    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::App;
    }
}