<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Variable\Registry\VariableDefinition;
use App\Environment\Variable\Registry\VariableRegistry;

final class DefaultFieldRulesFactory
{
    public function __construct(
        protected VariableRegistry $registry
    ) {}

    /**
     * Build FieldRules for all known variable definitions.
     *
     * @return FieldRules[]
     */
    public function make(): array
    {
        return collect($this->registry->all())
            ->map(fn (VariableDefinition $definition) => $definition->fieldRules())
            ->values()
            ->all();
    }
}
