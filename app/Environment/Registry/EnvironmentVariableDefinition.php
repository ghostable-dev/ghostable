<?php

namespace App\Environment\Registry;

use App\Environment\Enums\EnvironmentVariableGroup;

abstract class EnvironmentVariableDefinition
{
    abstract public function key(): string;

    public function rule(): string
    {
        return 'nullable|string';
    }

    public function description(): ?string
    {
        return null;
    }

    public function suggestedValues(): array
    {
        return [];
    }

    public function inputType(): ?string
    {
        return null; // e.g., 'text', 'boolean', 'number', 'select'
    }
    
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Other;
    }
}