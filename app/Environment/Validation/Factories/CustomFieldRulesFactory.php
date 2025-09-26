<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Actions\ResolveEnvironmentVariableRules;
use App\Environment\Validation\Contracts\MakesValidationPlan;
use App\Environment\Validation\Entities\EnvironmentValidationPlan;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Models\EnvironmentVariableRule;

class CustomFieldRulesFactory implements MakesValidationPlan
{
    public function __construct(protected KeyRuleProviderCollectionFactory $providerFactory) {}

    public function make(Environment $environment): EnvironmentValidationPlan
    {
        $fieldRules = resolve(ResolveEnvironmentVariableRules::class)
            ->handle($environment)
            ->reject(fn (EnvironmentVariableRule $rule) => $rule->is_deleted)
            ->map(fn (EnvironmentVariableRule $rule) => $this->makeFromRule($rule))
            ->all();

        return new EnvironmentValidationPlan(fieldRules: $fieldRules);
    }

    public function makeFromRule(EnvironmentVariableRule $rule): FieldRules
    {
        $parameters = RuleParameters::fromEnvironmentVariableRule($rule);

        return new FieldRules(
            key: $rule->key,
            providers: $this->providerFactory->makeFromEnvironmentVariableRule($rule, $parameters),
        );
    }
}
