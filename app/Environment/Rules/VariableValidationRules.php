<?php

namespace App\Environment\Rules;

use App\Environment\Enums\EnvironmentVariableRuleType;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class VariableValidationRules
{
    public static function createRules(): array
    {
        return [
            'key' => self::keyRules(),
            'is_required' => self::isRequiredRules(),
            'type' => self::typeRules(),
            'min_length' => self::minLengthRules(),
            'max_length' => self::maxLengthRules(),
            'min_value' => self::minValueRules(),
            'max_value' => self::maxValueRules(),
            'allowed_values' => self::allowedValuesRules(),
            'allowed_values.*' => self::allowedValuesItemRules(),
            'description' => self::descriptionRules(),
        ];
    }
    
    public static function keyRules(): array
    {
        return EnvVariableRules::keyRules();
    }

    public static function isRequiredRules(): array
    {
        return ['boolean'];
    }

    public static function minLengthRules(): array
    {
        return ['nullable', 'integer', 'min:0'];
    }

    public static function maxLengthRules(): array
    {
        return ['nullable', 'integer', 'min:0', 'gte:min_length'];
    }

    public static function minValueRules(): array
    {
        return ['nullable', 'integer'];
    }

    public static function maxValueRules(): array
    {
        return ['nullable', 'integer', 'gte:min_value'];
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
