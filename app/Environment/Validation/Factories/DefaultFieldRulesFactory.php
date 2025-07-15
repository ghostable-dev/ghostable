<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Registry\EnvironmentVariableDefinition;
use App\Environment\Registry\EnvironmentVariableRegistry;
use App\Environment\Validation\Entities\FieldRules;

final class DefaultFieldRulesFactory
{
    public function __construct(
        protected EnvironmentVariableRegistry $registry
    ) {}

    /**
     * Build FieldRules for all known variable definitions.
     *
     * @return FieldRules[]
     */
    public function make(): array
    {
        return collect($this->registry->all())
            ->map(fn (EnvironmentVariableDefinition $definition) => $definition->fieldRules())
            ->values()
            ->all();
    }
}
