<?php

declare(strict_types=1);

namespace App\Integration\Support;

use Spatie\LaravelData\Data;

class IntegrationSettingsRegistry
{
    /**
     * @var array<string, class-string<Data>>
     */
    protected static array $map = [];

    public static function register(string $integrationKey, string $dataClass): void
    {
        static::$map[$integrationKey] = $dataClass;
    }

    public static function resolve(?string $integrationKey): ?string
    {
        if (! $integrationKey) {
            return null;
        }

        return static::$map[$integrationKey] ?? null;
    }
}
