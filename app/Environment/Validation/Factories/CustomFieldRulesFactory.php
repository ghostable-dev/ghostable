<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Models\Environment;
use App\Environment\Validation\Entities\FieldRules;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Models\EnvironmentVariableRule;

final class CustomFieldRulesFactory
{
    public function __construct(
        protected KeyRuleProviderCollectionFactory $providerFactory,
    ) {}

    /**
     * Build FieldRules for all user-defined rules
     * attached to the given environment.
     *
     * @return FieldRules[]
     */
    public function makeFromEnvironment(Environment $environment): array
    {
        return $environment->rules
            ->map(fn (EnvironmentVariableRule $rule) => $this->makeFromRule($rule))
            ->all();
    }

    /**
     * Build FieldRules from a single EnvironmentVariableRule.
     */
    public function makeFromRule(EnvironmentVariableRule $rule): FieldRules
    {
        $parameters = RuleParameters::fromEnvironmentVariableRule($rule);

        return new FieldRules(
            key: $rule->key,
            providers: $this->providerFactory->makeFromEnvironmentVariableRule($rule, $parameters),
        );
    }
}
