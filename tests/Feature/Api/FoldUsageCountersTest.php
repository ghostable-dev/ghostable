<?php

use App\Api\Helpers\UsageCacheKey;
use App\Api\Helpers\UsageDate;
use App\Api\Jobs\FoldUsageCounters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('logs warning when encountering invalid portable aggregate key', function () {
    config()->set('cache.default', 'array');
    Cache::store()->clear();

    $now = Carbon::create(2025, 8, 27, 14, 44, 0, 'UTC');
    Carbon::setTestNow($now);

    $bucket = UsageDate::formatBucket($now->copy()->subMinute());
    $indexKey = UsageCacheKey::index($bucket);
    $invalidKey = 'invalid:key';

    Cache::put($indexKey, [$invalidKey], $now->copy()->addMinute());
    Cache::put($invalidKey, 1, $now->copy()->addMinute());

    Log::shouldReceive('warning')
        ->once()
        ->with('Skipping portable aggregate key', ['key' => $invalidKey]);

    (new FoldUsageCounters)->handle();

    Carbon::setTestNow();
});
