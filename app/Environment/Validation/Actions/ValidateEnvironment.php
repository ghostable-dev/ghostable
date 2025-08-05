<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Actions\ResolveEnvironmentVariables;
use App\Environment\Models\Environment;
use App\Environment\Validation\Factories\EnvironmentValidationPlanFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ValidateEnvironment
{
    public function __construct(
        protected EnvironmentValidationPlanFactory $planFactory
    ) {}

    /**
     * Validate all variables on the given environment using all known rule sources.
     *
     * @throws ValidationException
     */
    public function handle(Environment $environment): void
    {
        $data = resolve(ResolveEnvironmentVariables::class)
            ->handle($environment)
            ->mapWithKeys(fn ($v) => [$v->key => $v->value])
            ->toArray();
        
        $rules = $this->buildRules($environment, $data);

        Validator::make(
            data: $data,
            rules: $rules,
            attributes: $this->attributeNames($rules),
            messages: $this->buildMessages($environment),
        )->validate();
    }

    /**
     * Convert FieldRules into a Laravel-compatible validation rule array.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array<int, string|\Closure|\Illuminate\Contracts\Validation\ValidationRule>>
     */
    protected function buildRules(Environment $environment, array $data): array
    {
        $fieldRules = $this->planFactory->make($environment);

        $ruleMap = [];

        foreach ($fieldRules as $field) {
            $ruleMap[$field->key] = collect($field->providers)
                ->flatMap(fn ($provider) => (array) $provider->rule())
                ->values()
                ->all();
        }

        return $ruleMap;
    }

    /**
     * Build a map of custom validation messages from the FieldRules.
     *
     * @return array<string, string>
     */
    protected function buildMessages(Environment $environment): array
    {
        $fieldRules = $this->planFactory->make($environment);

        $messages = [];

        foreach ($fieldRules as $field) {
            foreach ($field->providers as $provider) {
                // We assume each provider handles a single rule
                $rule = $provider->rule();

                // If it's a string like "min:3", extract the rule name
                if (is_string($rule)) {
                    $ruleName = explode(':', $rule)[0];
                } elseif (is_object($rule)) {
                    $ruleName = class_basename($rule);
                } elseif ($rule instanceof \Closure) {
                    continue; // Laravel doesn’t support named messages for closures
                } else {
                    continue;
                }

                $messages["{$field->key}.{$ruleName}"] = $provider->message();
            }
        }

        return $messages;
    }

    /**
     * Map rule keys back to human-readable attribute names for error messages.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, string>
     */
    private function attributeNames(array $rules): array
    {
        return array_combine(
            array_keys($rules),
            array_keys($rules)
        );
    }
}
