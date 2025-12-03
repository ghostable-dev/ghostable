<?php

namespace App\Environment\Variable\Registry;

use App\Environment\Variable\Enums\VariableGroup;

abstract class VariableDefinition
{
    /**
     * The environment variable key (e.g. APP_NAME)
     */
    abstract public function key(): string;

    /**
     * Optional description of the variable.
     */
    public function description(): ?string
    {
        return null;
    }

    /**
     * Suggested values to show in UI or validate against.
     *
     * @return array<int, string>
     */
    public function suggestedValues(): array
    {
        return [];
    }

    /**
     * Group used to categorize this key in the UI.
     */
    public function group(): VariableGroup
    {
        return VariableGroup::Other;
    }

    /**
     * Rule providers to apply for this key.
     *
     * Validation providers are no longer used; retained for compatibility.
     *
     * @return array<int, mixed>
     */
    public function ruleProviders(): array
    {
        return [];
    }
}
