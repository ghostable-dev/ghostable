<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Contracts\MakesValidationPlan;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;
use App\Environment\Variable\Registry\VariableDefinition;
use App\Environment\Variable\Registry\VariableRegistry;

class DefaultFieldRulesFactory implements MakesValidationPlan
{
    public function __construct(protected VariableRegistry $registry) {}

    public function make(Environment $environment): EnvironmentValidationPlan
    {
        $fieldRules = collect($this->registry->all())
            ->map(fn (VariableDefinition $definition) => $definition->fieldRules())
            ->values()
            ->all();

        return new EnvironmentValidationPlan(fieldRules: $fieldRules);
    }
}
