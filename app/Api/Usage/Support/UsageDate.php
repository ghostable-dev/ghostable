<?php

declare(strict_types=1);

namespace App\Api\Usage\Support;

use Illuminate\Support\Carbon;

final class UsageDate
{
    public const TIMEZONE = 'UTC';

    public const BUCKET_FORMAT = 'Ymd\\THi';

    public static function now(): Carbon
    {
        return Carbon::now(self::TIMEZONE);
    }

    public static function formatBucket(Carbon $time): string
    {
        return $time->format(self::BUCKET_FORMAT);
    }

    public static function parseBucket(string $bucket): Carbon
    {
        return Carbon::createFromFormat(self::BUCKET_FORMAT, $bucket, self::TIMEZONE)
            ->startOfMinute();
    }
}
