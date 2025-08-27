<?php

namespace App\Api\Jobs;

use App\Api\Actions\UpsertApiUsageDaily;
use App\Api\Actions\UpsertApiUsageHourly;
use App\Api\Entities\UsageBucketData;
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
        $indexKey = "usage:index:{$bucket}";
        $keys = $redis->smembers($indexKey);
        if (empty($keys)) {
            return;
        }

        $results = $redis->pipeline(function ($pipe) use ($keys) {
            foreach ($keys as $k) {
                $pipe->get($k);
                $pipe->hgetall($k.':byres');
            }
        });

        DB::transaction(function () use ($keys, $results, $indexKey, $redis) {
            for ($i = 0; $i < count($keys); $i++) {
                $this->foldRedisKey($keys[$i], (int) $results[$i * 2], $results[$i * 2 + 1] ?? []);
            }

            $del = [$indexKey];
            foreach ($keys as $k) {
                $del[] = $k;
                $del[] = $k.':byres';
            }
            $redis->del($del);
        });
    }

    /**
     * @param  array<string,string>  $byRes
     */
    private function foldRedisKey(string $key, int $total, array $byRes): void
    {
        $parts = explode(':', $key, 7);
        if (count($parts) !== 7 || $parts[0] !== 'usage' || $parts[1] !== 'minute' || $total <= 0) {
            return;
        }
        [, , $bucketStr, $orgId, $tokenId, $method, $endpoint] = $parts;

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
        $indexKey = "usage:index:{$bucket}";
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

                if (str_contains($k, ':res:')) {
                    $this->foldPortableResourceKey($k, (int) $val);
                } else {
                    $this->foldPortableAggregateKey($k, (int) $val);
                }

                $store->forget($k);
            }

            $store->forget($indexKey);
        });
    }

    private function foldPortableResourceKey(string $key, int $cnt): void
    {
        $parts = explode(':', $key, 11);
        if (count($parts) < 10) {
            return;
        }
        [, , $bucketStr, $orgId, $tokenId, $method, $endpoint, , $rtype, $rid] = $parts;

        $data = UsageBucketData::fromBucket($bucketStr, $endpoint);
        $rtypeLimited = Str::limit($rtype, 50, '');

        resolve(UpsertApiUsageHourly::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->hourUtc, $cnt, $rtypeLimited, (string) $rid);

        resolve(UpsertApiUsageDaily::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->dayUtc, $cnt, $rtypeLimited, (string) $rid);
    }

    private function foldPortableAggregateKey(string $key, int $cnt): void
    {
        $parts = explode(':', $key, 7);
        if (count($parts) !== 7) {
            return;
        }
        [, , $bucketStr, $orgId, $tokenId, $method, $endpoint] = $parts;

        $data = UsageBucketData::fromBucket($bucketStr, $endpoint);

        resolve(UpsertApiUsageHourly::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->hourUtc, $cnt);

        resolve(UpsertApiUsageDaily::class)
            ->handle($orgId, $tokenId, $method, $data->endpoint, $data->dayUtc, $cnt);
    }
}
