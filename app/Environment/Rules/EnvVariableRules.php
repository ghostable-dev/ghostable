<?php

namespace App\Environment\Rules;

use App\Environment\Models\Environment;

class EnvVariableRules
{
    public static function create(Environment $environment): array
    {
        return [
            'key' => self::createKeyRules($environment),
            'value' => self::valueRules(),
        ];
    }

    public static function createKeyRules(Environment $environment): array
    {
        return array_merge(self::keyRules(), [
            new UniqueEnvKey($environment),
        ]);
    }

    public static function keyRules(): array
    {
        return ['required', new ValidEnvKey];
    }

    public static function valueRules(): array
    {
        return ['required', 'string'];
    }
}
