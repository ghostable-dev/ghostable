<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;
use App\Environment\Models\EnvironmentVariable;

class EnvVariableRules
{
    /**
     * Validation rules for creating a new environment variable.
     */
    public static function create(Environment $environment): array
    {
        return [
            'key' => self::createKeyRules($environment),
            'value' => self::valueRules(),
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
        return ['required', new ValidEnvKey];
    }

    /**
     * Rules for validating the value field.
     */
    public static function valueRules(): array
    {
        return ['required', 'string'];
    }
}
