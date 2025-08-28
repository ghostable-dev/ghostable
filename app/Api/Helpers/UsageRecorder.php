<?php

declare(strict_types=1);

namespace App\Api\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class UsageRecorder
{
    public function __construct(private readonly int $ttlSeconds = 900) {}

    public function record(
        string $orgId,
        string $tokenId,
        string $method,
        string $endpoint,
        ?string $resourceType = null,
        string|int|null $resourceId = null,
        ?int $status = null,
    ): void {
        Log::info('RECORDING...');
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
        $byResKey = UsageCacheKey::byResource($counterKey);

        // Try Redis-specific path
        $redis = method_exists($store->getStore(), 'connection') ? $store->getStore()->connection() : null;

        if ($redis) {
            Log::info('RECORDING WITH REDIS');
            $redis->pipeline(function ($pipe) use ($counterKey, $indexKey, $byResKey, $expires, $resourceType, $resourceId) {
                $pipe->incr($counterKey);
                $pipe->expireAt($counterKey, $expires->timestamp);
                $pipe->sadd($indexKey, [$counterKey]);
                $pipe->expireAt($indexKey, $expires->timestamp);

                if ($resourceType !== null && $resourceId !== null) {
                    $pipe->hincrby($byResKey, "{$resourceType}:{$resourceId}", 1);
                    $pipe->expireAt($byResKey, $expires->timestamp);
                }

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

        if ($resourceType !== null && $resourceId !== null) {
            // simulate per-resource using extra counter keys
            $resKey = UsageCacheKey::resourceKeyFromCounter(
                counterKey: $counterKey,
                resourceType: $resourceType,
                resourceId: $resourceId,
            );
            $store->add($resKey, 0, $expires);
            $store->increment($resKey);
        }

        // keep a per-minute list of keys; best-effort concurrency
        $list = $store->get($indexKey, []);
        if (! in_array($counterKey, $list, true)) {
            $list[] = $counterKey;
        }
        // also index the resKey so the rollup can find it easily
        if (isset($resKey) && ! in_array($resKey, $list, true)) {
            $list[] = $resKey;
        }
        $store->put($indexKey, $list, $expires);
    }
}
