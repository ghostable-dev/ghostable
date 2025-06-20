<?php

namespace App\Environment\Actions;

use App\Environment\Models\Environment;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Environment\Registry\EnvironmentVariableRegistry;
use Illuminate\Support\MessageBag;
use App\Environment\Contracts\EnvironmentValidationPolicy;
use App\Environment\Policies\ProductionEnvironmentPolicy;

class ValidateEnvironment
{
    /**
     * Validate all environment variables for a given environment instance.
     *
     * @throws ValidationException if any validation errors are found
    */
    public function handle(Environment $environment): void
    {
        $errors = new MessageBag();

        // Layer 1: Per-key validation rules (override or fallback to registry)
        $rules = $this->buildValidationRules($environment);
        $data = $this->collectVariableData($environment);

        // Layer 2: Required key presence validation
        $errors->merge($this->validateMissingRequiredKeys($environment));
        $errors->merge(Validator::make(
            data: $data, 
            rules: $rules, 
            attributes: collect($rules)->keys()->mapWithKeys(fn ($key) => [$key => $key])->all()
        )->errors());
        
        // Layer 3: Environment-specific validation policies (e.g. production-only rules)
        $errors->merge($this->runValidationPolicies($environment));

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->toArray());
        }
    }
    
    /**
     * Build the validation rules for all environment variables in the given environment.
     *
     * This includes:
     * - Any variable currently set in the environment
     * - Any custom validation override defined, even if the key is not yet present
     *
     * Each rule is resolved by checking:
     * 1. A custom override (environment_variable_rules)
     * 2. A default from the registry
     * 3. A fallback of 'nullable|string'
     *
     * @return array<string, string> An array of validation rules keyed by variable name
     */
    protected function buildValidationRules(Environment $environment): array
    {
        $registry = app(EnvironmentVariableRegistry::class);
        $customRules = $environment->rules->keyBy('key');
        $variableKeys = $environment->variables->pluck('key')->all();

        // Union of keys from existing variables and custom rules
        $allKeys = collect($variableKeys)
            ->merge($customRules->keys())
            ->unique();

        return $allKeys->mapWithKeys(function (string $key) use ($customRules, $registry) {
            $customRule = $customRules[$key]->rule ?? null;
            $defaultRule = $registry->get($key)?->rule();

            return [$key => $customRule ?? $defaultRule ?? 'nullable|string'];
        })->toArray();
    }
    
    /**
     * Collect the current key-value pairs for all variables in the environment.
     *
     * @return array<string, mixed> An associative array of variable keys to their values
     */
    protected function collectVariableData(Environment $environment): array
    {
        return $environment->variables->mapWithKeys(fn ($variable) => [
            $variable->key => $variable->value
        ])->toArray();
    }
    
    /**
     * Validate that all required keys defined in the registry (or overridden) are present
     * in the environment's variable set.
     *
     * A key is considered required if its validation rule includes the 'required' constraint.
     * Custom rules (from the environment) take precedence over default registry rules.
     */
    protected function validateMissingRequiredKeys(Environment $environment): MessageBag
    {
        $registry = app(EnvironmentVariableRegistry::class);
        $customRules = $environment->rules->keyBy('key');
        $existingKeys = $environment->variables->pluck('key')->all();
        $errors = new MessageBag();

        foreach ($registry->all() as $definition) {
            $key = $definition->key();
            $rule = $customRules[$key]->value ?? $definition->rule();

            if (str_contains($rule, 'required') && !in_array($key, $existingKeys)) {
                $errors->add($key, "The {$key} key is required but missing from the environment.");
            }
        }

        return $errors;
    }
    
    /**
     * Run all registered environment validation policies against the given environment.
     *
     * These policies apply environment-type or context-specific rules that may not be
     * tied to individual variable definitions (e.g., production-only constraints).
     * Policies are registered in the config/environment.php file.
     */
    protected function runValidationPolicies(Environment $environment): MessageBag
    {
        $errors = new MessageBag();

        foreach ([
            ProductionEnvironmentPolicy::class
        ] as $policyClass) {
            /** @var EnvironmentValidationPolicy $policy */
            $policy = app($policyClass);
            $messages = $policy->validate($environment);

            foreach ($messages as $key => $message) {
                $errors->add($key, $message);
            }
        }

        return $errors;
    }
}