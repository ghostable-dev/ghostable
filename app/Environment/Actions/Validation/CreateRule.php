<?php

namespace App\Environment\Actions\Validation;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariableRule;
use App\Environment\Enums\EnvironmentVariableRuleType;

class CreateRule
{
    public function handle(
        Environment $environment,
        string $key,
        bool $isRequired,
        EnvironmentVariableRuleType $type,
        array $settings,
        ?string $description = null
    ): EnvironmentVariableRule {
        // Build the new rule
        $rule = new EnvironmentVariableRule([
            'key'            => $key,
            'is_required'    => $isRequired,
            'type'           => $type->value,
            'min_length'     => $settings['min_length']     ?? null,
            'max_length'     => $settings['max_length']     ?? null,
            'min_value'      => $settings['min_value']      ?? null,
            'max_value'      => $settings['max_value']      ?? null,
            'allowed_values' => $settings['allowed_values'] ?? [],
            'description'    => $description,
        ]);

        // Associate to environment and persist
        $rule->environment()->associate($environment);
        $rule->save();

        return $rule;
    }
}