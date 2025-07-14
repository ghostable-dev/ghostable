<?php

namespace App\Environment\Validation\Rules;

use App\Environment\Models\Environment;
use App\Environment\Rules\EnvVariableRules;
use App\Environment\Validation\Enums\EnvironmentVariableRuleType;
use App\Environment\Validation\Models\EnvironmentVariableRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class VariableRuleFormRules
{
    public static function createRules(Environment $environment): array
    {
        return [
            'key' => self::keyRules($environment),
            'is_required' => self::isRequiredRules(),
            'type' => self::typeRules(),
            'min' => self::minRules(),
            'max' => self::maxRules(),
            'allowed_values' => self::allowedValuesRules(),
            'allowed_values.*' => self::allowedValuesItemRules(),
            'description' => self::descriptionRules(),
        ];
    }
    
    public static function updateRules(EnvironmentVariableRule $rule): array
    {
        return [
            'key' => self::keyUpdateRules($rule),
            'is_required' => self::isRequiredRules(),
            'type' => self::typeRules(),
            'min' => self::minRules(),
            'max' => self::maxRules(),
            'allowed_values' => self::allowedValuesRules(),
            'allowed_values.*' => self::allowedValuesItemRules(),
            'description' => self::descriptionRules(),
        ];
    }
    
    public static function keyRules(Environment $environment): array
    {
        return array_merge(
            EnvVariableRules::keyRules(),
            [
                Rule::unique('environment_variable_rules', 'key')
                    ->where('environment_id', $environment->id),
            ]
        );
    }
    
    public static function keyUpdateRules(EnvironmentVariableRule $rule): array
    {
        return array_merge(
            EnvVariableRules::keyRules(),
            [
                Rule::unique('environment_variable_rules', 'key')
                    ->where('environment_id', $rule->environment_id)
                    ->ignore($rule->id),
            ]
        );
    }

    public static function isRequiredRules(): array
    {
        return ['boolean'];
    }

    public static function minRules(): array
    {
        return ['nullable', 'integer', 'min:0'];
    }

    public static function maxRules(): array
    {
        return ['nullable', 'integer', 'min:0', 'gte:min'];
    }

    public static function allowedValuesRules(): array
    {
        return ['nullable', 'array'];
    }

    public static function allowedValuesItemRules(): array
    {
        return ['string'];
    }

    public static function descriptionRules(): array
    {
        return ['nullable', 'string'];
    }
    
    public static function typeRules(): array
    {
        return ['required', new Enum(EnvironmentVariableRuleType::class)];
    }
}
