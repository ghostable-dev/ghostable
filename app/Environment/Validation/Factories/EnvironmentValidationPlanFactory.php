<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\FieldRules;

final class EnvironmentValidationPlanFactory
{
    public function __construct(
        protected CustomFieldRulesFactory $customFactory,
        protected EnvironmentTypeFieldRulesFactory $typeFactory,
        protected DefaultFieldRulesFactory $defaultFactory,
    ) {}

    /**
     * Build the full set of FieldRules for a given environment.
     *
     * @return FieldRules[]
     */
    public function make(Environment $environment): array
    {
        $custom = $this->customFactory->makeFromEnvironment($environment);

        $type = $this->typeFactory->makeFromType($environment->type);

        $defaults = $this->defaultFactory->make();

        // Prefer custom rules if key conflicts exist
        $merged = collect($defaults)
            ->keyBy(fn (FieldRules $r) => $r->key)
            ->merge(collect($type)->keyBy(fn ($r) => $r->key))
            ->merge(collect($custom)->keyBy(fn ($r) => $r->key))
            ->values()
            ->all();

        return $merged;
    }
}
