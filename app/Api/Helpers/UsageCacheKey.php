<?php

declare(strict_types=1);

namespace App\Api\Helpers;

use App\Api\Entities\UsageCacheAggregateData;

final class UsageCacheKey
{
    public const PREFIX = 'usage';

    public const PERIOD = 'minute';

    public const INDEX_PREFIX = self::PREFIX.':index';

    public static function counter(
        string $bucket,
        string $orgId,
        string $tokenId,
        string $method,
        string $endpoint,
    ): string {
        return sprintf(
            '%s:%s:%s:%s:%s:%s',
            self::PREFIX.':'.self::PERIOD,
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

    public static function parseAggregate(string $key): ?UsageCacheAggregateData
    {
        $parts = explode(':', $key, 7);
        if (count($parts) !== 7 || $parts[0] !== self::PREFIX || $parts[1] !== self::PERIOD) {
            return null;
        }

        [, , $bucket, $orgId, $tokenId, $method, $endpoint] = $parts;

        return new UsageCacheAggregateData($bucket, $orgId, $tokenId, $method, $endpoint);
    }
}
