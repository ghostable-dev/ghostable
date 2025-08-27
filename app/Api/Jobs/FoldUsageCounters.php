<?php

namespace App\Api\Jobs;

use App\Api\Actions\UpsertApiUsageDaily;
use App\Api\Actions\UpsertApiUsageHourly;
use App\Api\Entities\UsageBucketData;
use App\Api\Helpers\UsageCacheKey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FoldUsageCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $lookbackMinutes = 3,
        private readonly bool $rollupResources = true,
    ) {}

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
     * @return array<int,string>
     */
    private function recentBuckets(): array
    {
        $now = Carbon::now('UTC')->startOfMinute();
        $buckets = [];
        for ($i = 1; $i <= $this->lookbackMinutes; $i++) {
            $buckets[] = $now->copy()->subMinutes($i)->format('Ymd\THi');
        }

        return $buckets;
    }

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
                $pipe->hgetall(UsageCacheKey::byResource($k));
            }
        });

        DB::transaction(function () use ($keys, $results, $indexKey, $redis) {
            for ($i = 0; $i < count($keys); $i++) {
                $this->foldRedisKey($keys[$i], (int) $results[$i * 2], $results[$i * 2 + 1] ?? []);
            }

            $del = [$indexKey];
            foreach ($keys as $k) {
                $del[] = $k;
                $del[] = UsageCacheKey::byResource($k);
            }
            $redis->del($del);
        });
    }

    /**
     * @param  array<string,string>  $byRes
     */
    private function foldRedisKey(string $key, int $total, array $byRes): void
    {
        $parts = UsageCacheKey::parseAggregate($key);
        if ($parts === null || $total <= 0) {
            return;
        }
        $bucketStr = $parts->bucket;
        $orgId = $parts->orgId;
        $tokenId = $parts->tokenId;
        $method = $parts->method;
        $endpoint = $parts->endpoint;

        $data = UsageBucketData::fromBucket($bucketStr, $endpoint);

        resolve(UpsertApiUsageHourly::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->hourUtc, $total);

        resolve(UpsertApiUsageDaily::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->dayUtc, $total);

        if (! $this->rollupResources || empty($byRes)) {
            return;
        }

        foreach ($byRes as $field => $cntStr) {
            $cnt = (int) $cntStr;
            if ($cnt <= 0) {
                continue;
            }

            [$rtype, $rid] = explode(':', $field, 2) + [null, null];
            if (! $rtype || ! $rid) {
                continue;
            }

            $rtypeLimited = Str::limit($rtype, 50, '');

            resolve(UpsertApiUsageHourly::class)
                ->handle($orgId, $tokenId, $method, $data->endpoint, $data->hourUtc, $cnt, $rtypeLimited, (string) $rid);

            resolve(UpsertApiUsageDaily::class)
                ->handle($orgId, $tokenId, $method, $data->endpoint, $data->dayUtc, $cnt, $rtypeLimited, (string) $rid);
        }
    }

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

                if (UsageCacheKey::isResourceKey($k)) {
                    $this->foldPortableResourceKey($k, (int) $val);
                } else {
                    $this->foldPortableAggregateKey($k, count: (int) $val);
                }

                $store->forget($k);
            }

            $store->forget($indexKey);
        });
    }

    private function foldPortableResourceKey(string $key, int $cnt): void
    {
        $parts = UsageCacheKey::parseResource($key);
        if ($parts === null) {
            return;
        }
        $bucketStr = $parts->bucket;
        $orgId = $parts->orgId;
        $tokenId = $parts->tokenId;
        $method = $parts->method;
        $endpoint = $parts->endpoint;
        $rtype = $parts->resourceType;
        $rid = $parts->resourceId;

        $data = UsageBucketData::fromBucket($bucketStr, $endpoint);
        $rtypeLimited = Str::limit($rtype, 50, '');

        resolve(UpsertApiUsageHourly::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->hourUtc, $cnt, $rtypeLimited, (string) $rid);

        resolve(UpsertApiUsageDaily::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->dayUtc, $cnt, $rtypeLimited, (string) $rid);
    }

    private function foldPortableAggregateKey(string $key, int $count): void
    {
        if (($parts = UsageCacheKey::parseAggregate($key)) === null) {
            return;
        }

        $data = UsageBucketData::fromBucket(bucket: $parts->bucket, endpoint: $parts->endpoint);

        resolve(UpsertApiUsageHourly::class)->handle(organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            hour: $data->hourUtc,
            count: $count
        );

        resolve(UpsertApiUsageDaily::class)->handle(
            organizationId: $parts->orgId,
            tokenId: $parts->tokenId,
            method: $parts->method,
            endpoint: $data->endpoint,
            day: $data->dayUtc,
            count: $count
        );
    }
}
