<?php

declare(strict_types=1);

namespace App\Api\Helpers;

use App\Api\Entities\UsageCacheAggregateData;
use App\Api\Entities\UsageCacheResourceData;

final class UsageCacheKey
{
    public const PREFIX = 'usage';
    public const PERIOD = 'minute';
    public const INDEX_PREFIX = self::PREFIX . ':index';
    public const RESOURCE_SEGMENT = 'res';
    public const BYRES_SUFFIX = ':byres';

    public static function counter(
        string $bucket,
        string $orgId,
        string $tokenId,
        string $method,
        string $endpoint,
    ): string {
        return sprintf(
            '%s:%s:%s:%s:%s:%s',
            self::PREFIX . ':' . self::PERIOD,
            $bucket,
            $orgId,
            $tokenId,
            $method,
            $endpoint,
        );
    }

    public static function index(string $bucket): string
    {
        return sprintf('%s:%s', self::INDEX_PREFIX, $bucket);
    }

    public static function byResource(string $counterKey): string
    {
        return $counterKey . self::BYRES_SUFFIX;
    }

    public static function resourceKeyFromCounter(
        string $counterKey,
        string $resourceType,
        string|int $resourceId,
    ): string {
        return sprintf(
            '%s:%s:%s:%s',
            $counterKey,
            self::RESOURCE_SEGMENT,
            $resourceType,
            $resourceId,
        );
    }

    public static function isResourceKey(string $key): bool
    {
        return str_contains($key, ':' . self::RESOURCE_SEGMENT . ':');
    }

    public static function parseAggregate(string $key): ?UsageCacheAggregateData
    {
        $parts = explode(':', $key, 7);
        if (count($parts) !== 7 || $parts[0] !== self::PREFIX || $parts[1] !== self::PERIOD) {
            return null;
        }

        [, , $bucket, $orgId, $tokenId, $method, $endpoint] = $parts;

        return new UsageCacheAggregateData($bucket, $orgId, $tokenId, $method, $endpoint);
    }

    public static function parseResource(string $key): ?UsageCacheResourceData
    {
        $parts = explode(':', $key, 11);
        if (
            count($parts) < 10
            || $parts[0] !== self::PREFIX
            || $parts[1] !== self::PERIOD
            || ($parts[7] ?? null) !== self::RESOURCE_SEGMENT
        ) {
            return null;
        }

        [, , $bucket, $orgId, $tokenId, $method, $endpoint, , $resourceType, $resourceId] = $parts;

        return new UsageCacheResourceData(
            $bucket,
            $orgId,
            $tokenId,
            $method,
            $endpoint,
            $resourceType,
            (string) $resourceId,
        );
    }
}
