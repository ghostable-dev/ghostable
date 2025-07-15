<?php

namespace App\Environment\Registry;

use App\Environment\Enums\EnvironmentVariableGroup;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Rules\RequiredKeyRule;
use App\Environment\Validation\Rules\StringKeyRule;

abstract class EnvironmentVariableDefinition
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
    public function group(): EnvironmentVariableGroup
    {
        return EnvironmentVariableGroup::Other;
    }

    /**
     * Default "required" rule provider. Override if needed.
     */
    public function requiredProvider(): RequiredKeyRule
    {
        return new RequiredKeyRule;
    }

    /**
     * Return the FieldRules for this definition.
     */
    public function fieldRules(): FieldRules
    {
        return new FieldRules(
            key: $this->key(),
            providers: $this->ruleProviders(),
        );
    }

    /**
     * Rule providers to apply for this key.
     *
     * @return array<int, \App\Environment\Validation\Contracts\KeyRuleProvider>
     */
    public function ruleProviders(): array
    {
        return [
            new StringKeyRule,
        ];
    }
}
