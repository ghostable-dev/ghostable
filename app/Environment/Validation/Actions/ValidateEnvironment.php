<?php

namespace App\Environment\Validation\Actions;

use App\Environment\Models\Environment;
use App\Environment\Resolvers\ResolveEnvironmentVariables;
use App\Environment\Validation\Factories\EnvironmentValidationPlanFactory;
use Illuminate\Support\Facades\Validator;

class ValidateEnvironment
{
    public function __construct(
        protected EnvironmentValidationPlanFactory $planFactory
    ) {}

    public function handle(Environment $environment, ?array $data = null): void
    {
        // Use resolved effective vars if none supplied
        $data ??= app(ResolveEnvironmentVariables::class)
            ->handle($environment)
            ->mapWithKeys(fn ($v) => [$v->variable->key => $v->variable->value])
            ->toArray();

        $plan = $this->planFactory->make($environment);

        // Build field rules & messages
        $fieldRules = $this->buildFieldRuleMap($plan->fields());
        $messages = $this->buildMessages($plan->fields());

        // Attach env-level rules under a dummy key so they run in the same validator
        if (! empty($plan->env())) {
            $data['__env__'] = true;
            $fieldRules['__env__'] = $plan->env(); // array of ValidationRule
            // Optional: nicer label
            $messages['__env__.EnsureVaporBudgetNotExceeded'] ??= 'Environment configuration is invalid.';
        }

        Validator::make(
            data: $data,
            rules: $fieldRules,
            attributes: $this->attributeNames($fieldRules),
            messages: $messages,
        )->validate();
    }

    /** @param \App\Environment\Validation\Entities\FieldRules[] $fieldRuleObjs */
    protected function buildFieldRuleMap(array $fieldRuleObjs): array
    {
        $rules = [];
        foreach ($fieldRuleObjs as $fr) {
            $rules[$fr->key] = collect($fr->providers)
                ->flatMap(fn ($provider) => (array) $provider->rule())
                ->values()
                ->all();
        }

        return $rules;
    }

    /** @param \App\Environment\Validation\Entities\FieldRules[] $fieldRuleObjs */
    protected function buildMessages(array $fieldRuleObjs): array
    {
        $messages = [];
        foreach ($fieldRuleObjs as $fr) {
            foreach ($fr->providers as $provider) {
                $rule = $provider->rule();
                if (is_string($rule)) {
                    $name = explode(':', $rule)[0];
                } elseif (is_object($rule)) {
                    $name = class_basename($rule);
                } else {
                    continue;
                }
                $messages["{$fr->key}.{$name}"] = $provider->message();
            }
        }

        return $messages;
    }

    private function attributeNames(array $rules): array
    {
        // Give the dummy key a human label
        $names = array_keys($rules);
        $labels = array_map(fn ($k) => $k === '__env__' ? 'environment' : $k, $names);

        return array_combine($names, $labels);
    }
}
