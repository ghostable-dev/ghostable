<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Contracts\MakesValidationPlan;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;
use App\Environment\Validation\Entities\FieldRules;
use Illuminate\Contracts\Validation\ValidationRule;

class EnvironmentValidationPlanFactory implements MakesValidationPlan
{
    public function __construct(
        protected DefaultFieldRulesFactory $defaultFactory,
        protected EnvironmentTypeFieldRulesFactory $typeFactory,
        protected DeploymentProviderFieldRulesFactory $providerFactory,
        protected CustomFieldRulesFactory $customFactory,
    ) {}

    public function make(Environment $environment): EnvironmentValidationPlan
    {
        $defaults = $this->defaultFactory->make($environment);
        $type = $this->typeFactory->make($environment);
        $provider = $this->providerFactory->make($environment);
        $custom = $this->customFactory->make($environment);

        // 1) Merge defaults + type + provider (append providers on collisions)
        $base = $this->mergeFieldRuleSets(
            $defaults->fields(),
            $type->fields(),
            $provider->fields(),
        );

        // 2) Custom wins: overwrite keys entirely when present
        foreach ($custom->fields() as $fr) {
            $base[$fr->key] = $fr;
        }

        // 3) Env-level rules: concat (dedupe by class, keeping last so custom wins)
        $envRules = $this->dedupeEnvRulesByClass([
            ...$defaults->env(),
            ...$type->env(),
            ...$provider->env(),
            ...$custom->env(),
        ]);

        return new EnvironmentValidationPlan(
            fieldRules: array_values($base), // normalize numeric keys
            envRules: $envRules,
        );
    }

    /**
     * @param  FieldRules[][]  $sets
     * @return array<string, FieldRules>
     */
    private function mergeFieldRuleSets(array ...$sets): array
    {
        $byKey = [];

        foreach ($sets as $set) {
            foreach ($set as $incoming) {
                $k = $incoming->key;

                if (! isset($byKey[$k])) {
                    $byKey[$k] = $incoming;

                    continue;
                }

                // Merge providers if same key
                $byKey[$k]->providers = [
                    ...$byKey[$k]->providers,
                    ...$incoming->providers,
                ];
            }
        }

        return $byKey;
    }

    /**
     * @param  ValidationRule[]  $rules
     * @return ValidationRule[]
     */
    private function dedupeEnvRulesByClass(array $rules): array
    {
        $map = [];
        foreach ($rules as $rule) {
            // keep last occurrence (custom added last)
            $map[$rule::class] = $rule;
        }

        return array_values($map);
    }
}
