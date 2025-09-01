<?php

declare(strict_types=1);

namespace App\Api\Actions;

use App\Api\Helpers\UsageCacheKey;
use App\Api\Helpers\UsageDate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class RecordApiUsage
{
    public function __construct(private readonly int $ttlSeconds = 900) {}

    public function handle(
        string $orgId,
        string $tokenId,
        string $method,
        string $endpoint,
        ?int $status = null,
    ): void {
        $store = Cache::store();
        $now = UsageDate::now();
        $bucket = UsageDate::formatBucket($now); // e.g. 20250827T1445
        $expires = $now->copy()->addSeconds($this->ttlSeconds);

        $method = strtoupper($method);
        $endpoint = (string) Str::of($endpoint)->trim()->replace(' ', '');

        $counterKey = UsageCacheKey::counter(
            bucket: $bucket,
            orgId: $orgId,
            tokenId: $tokenId,
            method: $method,
            endpoint: $endpoint,
        );
        $indexKey = UsageCacheKey::index($bucket);

        // Try Redis-specific path
        $redis = method_exists($store->getStore(), 'connection') ? $store->getStore()->connection() : null;

        if ($redis) {
            $redis->pipeline(function ($pipe) use ($counterKey, $indexKey, $expires) {
                $pipe->incr($counterKey);
                $pipe->expireAt($counterKey, $expires->timestamp);
                $pipe->sadd($indexKey, $counterKey);
                $pipe->expireAt($indexKey, $expires->timestamp);

                // Optional status class breakdown (uncomment if you want it)
                // if ($status !== null) {
                //     $cls = intdiv($status, 100) . 'xx';
                //     $sk = $counterKey . ':status';
                //     $pipe->hincrby($sk, $cls, 1);
                //     $pipe->expireAt($sk, $expires->timestamp);
                // }
            });

            return;
        }

        // ---------- Portable fallback (array/file cache) ----------
        $store->add($counterKey, 0, $expires);
        $store->increment($counterKey);

        $list = $store->get($indexKey, []);
        if (! in_array($counterKey, $list, true)) {
            $list[] = $counterKey;
        }
        $store->put($indexKey, $list, $expires);
    }
}
