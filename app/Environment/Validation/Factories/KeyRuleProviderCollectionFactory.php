<?php

namespace App\Environment\Validation\Factories;

use App\Environment\Validation\Contracts\KeyRuleProvider;
use App\Environment\Validation\Entities\RuleParameters;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use App\Environment\Validation\Rules\RequiredKeyRule;
use InvalidArgumentException;

final class KeyRuleProviderCollectionFactory
{
    /**
     * @param array<EnvironmentVariableRuleType, class-string<KeyRuleProvider>> $typeMap
     */
    public function __construct(
        protected array $typeMap = []
    ) {}

    /**
     * Register a new rule type → provider mapping.
     */
    public function register(EnvironmentVariableRuleType $type, string $providerClass): void
    {
        $this->typeMap[$type->value] = $providerClass;
    }

    /**
     * Build KeyRuleProviders for a given EnvironmentVariableRule model.
     *
     * @return KeyRuleProvider[]
     */
    public function makeFromEnvironmentVariableRule(
        EnvironmentVariableRule $rule,
        RuleParameters $parameters
    ): array {
        $providers = [];

        if ($rule->is_required) {
            $providers[] = new RequiredKeyRule($parameters);
        }

        $typeRuleClass = $this->typeMap[$rule->type->value] ?? null;

        if (! $typeRuleClass) {
            throw new InvalidArgumentException("No KeyRuleProvider registered for type: {$rule->type->value}");
        }

        $providers[] = new $typeRuleClass($parameters);

        return $providers;
    }
}