<?php

namespace App\Environment\Variable\Rules;

use App\Environment\Models\Environment;
use App\Environment\Rules\UniqueEnvKey;
use App\Environment\Variable\Models\EnvironmentVariable;

class VariableRules
{
    /**
     * Validation rules for creating a new environment variable.
     */
    public static function create(Environment $environment): array
    {
        return [
            'key' => self::createKeyRules($environment),
            'value' => self::valueRules(),
            'vapor_secret' => self::vaporSecretRules(),
        ];
    }

    /**
     * Validation rules for updating an existing environment variable.
     * Allows the current key to remain unchanged while still enforcing uniqueness.
     */
    public static function update(): array
    {
        return [
            'value' => self::valueRules(),
            'vapor_secret' => self::vaporSecretRules(required: false),
        ];
    }

    /**
     * Rules for validating a key during creation.
     */
    public static function createKeyRules(Environment $environment): array
    {
        return array_merge(self::keyRules(), [
            new UniqueEnvKey($environment),
        ]);
    }

    /**
     * Rules for validating a key during update.
     */
    public static function updateKeyRules(
        Environment $environment,
        EnvironmentVariable $except
    ): array {
        return array_merge(self::keyRules(), [
            new UniqueEnvKey($environment, $except),
        ]);
    }

    /**
     * Base rules for all keys.
     */
    public static function keyRules(): array
    {
        return ['required', new ValidVariableKey];
    }

    /**
     * Rules for validating the value field.
     */
    public static function valueRules(): array
    {
        return ['required', 'string'];
    }

    public static function vaporSecretRules(bool $required = true): array
    {
        return [$required ? 'required' : 'sometimes', 'boolean'];
    }
}
