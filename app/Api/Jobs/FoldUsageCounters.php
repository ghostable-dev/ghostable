<?php

namespace App\Api\Jobs;

use App\Api\Actions\UpsertApiUsageDaily;
use App\Api\Actions\UpsertApiUsageHourly;
use App\Api\Entities\UsageBucketData;
use App\Api\Helpers\UsageCacheKey;
use App\Api\Helpers\UsageDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FoldUsageCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $lookbackMinutes = 3,
    ) {}

    /**
     * Fold usage counters from cache into persistent storage.
     */
    public function handle(): void
    {
        $store = Cache::store();
        $redis = method_exists($store->getStore(), 'connection')
            ? $store->getStore()->connection()
            : null;

        foreach ($this->recentBuckets() as $bucket) {
            if ($redis) {
                $this->foldRedisBucket(bucket: $bucket, redis: $redis);

                continue;
            }

            $this->foldPortableBucket(bucket: $bucket, store: $store);
        }
    }

    /**
     * Determine the recent bucket timestamps to process.
     */
    private function recentBuckets(): array
    {
        $now = UsageDate::now()->startOfMinute();
        $buckets = [];
        for ($i = 1; $i <= $this->lookbackMinutes; $i++) {
            $buckets[] = UsageDate::formatBucket($now->copy()->subMinutes($i));
        }

        return $buckets;
    }

    /**
     * Fold a Redis bucket of usage counters.
     */
    private function foldRedisBucket(string $bucket, $redis): void
    {
        $indexKey = UsageCacheKey::index($bucket);
        $keys = $redis->smembers($indexKey);
        if (empty($keys)) {
            return;
        }

        $results = $redis->pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $k) {
                $pipe->get($k);
            }
        });

        DB::transaction(function () use ($keys, $results, $indexKey, $redis) {
            for ($i = 0; $i < count($keys); $i++) {
                $this->foldRedisKey(
                    key: $keys[$i],
                    total: (int) $results[$i],
                );
            }

            $redis->del(array_merge([$indexKey], $keys));
        });
    }

    /**
     * Fold a single Redis aggregate key.
     */
    private function foldRedisKey(string $key, int $total): void
    {
        if (($parts = UsageCacheKey::parseAggregate($key)) === null
            || $total <= 0) {
            Log::warning('Skipping Redis key', ['key' => $key, 'total' => $total]);

            return;
        }

        $data = UsageBucketData::fromBucket(bucket: $parts->bucket, endpoint: $parts->endpoint);

        resolve(UpsertApiUsageHourly::class)->handle(
            organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            hour: $data->hourUtc,
            count: $total,
        );

        resolve(UpsertApiUsageDaily::class)->handle(
            organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            day: $data->dayUtc,
            count: $total,
        );
    }

    /**
     * Fold a portable cache bucket of usage counters.
     */
    private function foldPortableBucket(string $bucket, $store): void
    {
        $indexKey = UsageCacheKey::index($bucket);
        $keys = $store->get($indexKey, []);
        if (empty($keys)) {
            return;
        }

        DB::transaction(function () use ($keys, $store, $indexKey) {
            foreach ($keys as $k) {
                $val = $store->get($k);
                if ($val === null) {
                    continue;
                }

                $this->foldPortableAggregateKey(key: $k, count: (int) $val);
                $store->forget($k);
            }

            $store->forget($indexKey);
        });
    }

    /**
     * Fold a portable aggregate usage key.
     */
    private function foldPortableAggregateKey(string $key, int $count): void
    {
        if (($parts = UsageCacheKey::parseAggregate($key)) === null) {
            Log::warning('Skipping portable aggregate key', ['key' => $key]);

            return;
        }

        $data = UsageBucketData::fromBucket(bucket: $parts->bucket, endpoint: $parts->endpoint);

        resolve(UpsertApiUsageHourly::class)->handle(
            organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            hour: $data->hourUtc,
            count: $count,
        );

        resolve(UpsertApiUsageDaily::class)->handle(
            organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            day: $data->dayUtc,
            count: $count,
        );
    }
}
