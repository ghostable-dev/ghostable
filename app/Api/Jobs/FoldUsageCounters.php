<?php

namespace App\Api\Jobs;

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
        private readonly bool $rollupResources = true, // keep true to write per-resource rows into same tables
    ) {}

    public function handle(): void
    {
        $store = Cache::store();
        $redis = method_exists($store->getStore(), 'connection') ? $store->getStore()->connection() : null;

        $now = Carbon::now('UTC')->startOfMinute();
        $buckets = [];
        for ($i = 1; $i <= $this->lookbackMinutes; $i++) {
            $buckets[] = $now->copy()->subMinutes($i)->format('Ymd\THi'); // e.g. 20250827T1445
        }

        foreach ($buckets as $bucket) {
            $indexKey = "usage:index:{$bucket}";

            if ($redis) {
                $keys = $redis->smembers($indexKey);
                if (empty($keys)) {
                    continue;
                }

                // GET total + HGETALL resource breakdown for each counter key
                $results = $redis->pipeline(function ($pipe) use ($keys) {
                    foreach ($keys as $k) {
                        $pipe->get($k);
                        $pipe->hgetall($k.':byres');
                    }
                });

                DB::transaction(function () use ($keys, $results, $indexKey, $redis) {
                    for ($i = 0; $i < count($keys); $i++) {
                        $key = $keys[$i];
                        $total = (int) $results[$i * 2];
                        /** @var array<string,string> $byRes */
                        $byRes = $results[$i * 2 + 1] ?? [];

                        // usage:minute:{bucket}:{orgId}:{tokenId}:{method}:{endpoint}
                        $parts = explode(':', $key, 7);
                        if (count($parts) !== 7 || $parts[0] !== 'usage' || $parts[1] !== 'minute') {
                            continue;
                        }
                        [, , $bucketStr, $orgId, $tokenId, $method, $endpoint] = $parts;

                        if ($total <= 0) {
                            continue;
                        }

                        $endpoint = Str::limit($endpoint, 191, '');
                        $minuteUtc = Carbon::createFromFormat('Ymd\THi', $bucketStr, 'UTC')->startOfMinute();
                        $hourUtc = $minuteUtc->copy()->startOfHour();
                        $dayUtc = $minuteUtc->copy()->startOfDay();

                        // ---- aggregate rows (resource_type/id = NULL) ----
                        DB::table('api_usage_hourly')->upsert(
                            [
                                'organization_id' => $orgId,
                                'token_id' => $tokenId,
                                'method' => $method,
                                'endpoint' => $endpoint,
                                'resource_type' => null,
                                'resource_id' => null,
                                'hour' => $hourUtc,
                                'count' => $total,
                            ],
                            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
                            ['count' => DB::raw('api_usage_hourly.count + '.$total)]
                        );

                        DB::table('api_usage_daily')->upsert(
                            [
                                'organization_id' => $orgId,
                                'token_id' => $tokenId,
                                'method' => $method,
                                'endpoint' => $endpoint,
                                'resource_type' => null,
                                'resource_id' => null,
                                'date' => $dayUtc,
                                'count' => $total,
                            ],
                            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
                            ['count' => DB::raw('api_usage_daily.count + '.$total)]
                        );

                        // ---- per-resource rows (same tables) ----
                        if ($this->rollupResources && ! empty($byRes)) {
                            foreach ($byRes as $field => $cntStr) {
                                $cnt = (int) $cntStr;
                                if ($cnt <= 0) {
                                    continue;
                                }

                                // "{type}:{id}" — id should be a UUID string (36 chars)
                                [$rtype, $rid] = explode(':', $field, 2) + [null, null];
                                if (! $rtype || ! $rid) {
                                    continue;
                                }

                                DB::table('api_usage_hourly')->upsert(
                                    [
                                        'organization_id' => $orgId,
                                        'token_id' => $tokenId,
                                        'method' => $method,
                                        'endpoint' => $endpoint,
                                        'resource_type' => Str::limit($rtype, 50, ''),
                                        'resource_id' => (string) $rid,
                                        'hour' => $hourUtc,
                                        'count' => $cnt,
                                    ],
                                    ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
                                    ['count' => DB::raw('api_usage_hourly.count + '.$cnt)]
                                );

                                DB::table('api_usage_daily')->upsert(
                                    [
                                        'organization_id' => $orgId,
                                        'token_id' => $tokenId,
                                        'method' => $method,
                                        'endpoint' => $endpoint,
                                        'resource_type' => Str::limit($rtype, 50, ''),
                                        'resource_id' => (string) $rid,
                                        'date' => $dayUtc,
                                        'count' => $cnt,
                                    ],
                                    ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
                                    ['count' => DB::raw('api_usage_daily.count + '.$cnt)]
                                );
                            }
                        }
                    }

                    // cleanup (optional; TTL also clears)
                    $del = [$indexKey];
                    foreach ($keys as $k) {
                        $del[] = $k;
                        $del[] = $k.':byres';
                    }
                    $redis->del($del);
                });

                continue;
            }

            // ---------- Portable fallback (array/file cache) ----------
            $keys = $store->get($indexKey, []);
            if (empty($keys)) {
                continue;
            }

            DB::transaction(function () use ($keys, $store, $indexKey) {
                foreach ($keys as $k) {
                    $val = $store->get($k);
                    if ($val === null) {
                        continue;
                    }

                    $isResKey = str_contains($k, ':res:');

                    if ($isResKey) {
                        // usage:minute:{bucket}:{org}:{token}:{method}:{endpoint}:res:{type}:{id}
                        $parts = explode(':', $k, 11);
                        if (count($parts) < 10) {
                            continue;
                        }
                        [, , $bucketStr, $orgId, $tokenId, $method, $endpoint, , $rtype, $rid] = $parts;

                        $endpoint = Str::limit($endpoint, 191, '');
                        $minuteUtc = Carbon::createFromFormat('Ymd\THi', $bucketStr, 'UTC')->startOfMinute();
                        $hourUtc = $minuteUtc->copy()->startOfHour();
                        $dayUtc = $minuteUtc->copy()->startOfDay();

                        DB::table('api_usage_hourly')->upsert(
                            [
                                'organization_id' => $orgId,
                                'token_id' => $tokenId,
                                'method' => $method,
                                'endpoint' => $endpoint,
                                'resource_type' => Str::limit($rtype, 50, ''),
                                'resource_id' => (string) $rid,
                                'hour' => $hourUtc,
                                'count' => (int) $val,
                            ],
                            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
                            ['count' => DB::raw('api_usage_hourly.count + '.(int) $val)]
                        );

                        DB::table('api_usage_daily')->upsert(
                            [
                                'organization_id' => $orgId,
                                'token_id' => $tokenId,
                                'method' => $method,
                                'endpoint' => $endpoint,
                                'resource_type' => Str::limit($rtype, 50, ''),
                                'resource_id' => (string) $rid,
                                'date' => $dayUtc,
                                'count' => (int) $val,
                            ],
                            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
                            ['count' => DB::raw('api_usage_daily.count + '.(int) $val)]
                        );

                        $store->forget($k);

                        continue;
                    }

                    // main aggregate key
                    $parts = explode(':', $k, 7);
                    if (count($parts) !== 7) {
                        continue;
                    }
                    [, , $bucketStr, $orgId, $tokenId, $method, $endpoint] = $parts;

                    $endpoint = Str::limit($endpoint, 191, '');
                    $minuteUtc = Carbon::createFromFormat('Ymd\THi', $bucketStr, 'UTC')->startOfMinute();
                    $hourUtc = $minuteUtc->copy()->startOfHour();
                    $dayUtc = $minuteUtc->copy()->startOfDay();

                    DB::table('api_usage_hourly')->upsert(
                        [
                            'organization_id' => $orgId,
                            'token_id' => $tokenId,
                            'method' => $method,
                            'endpoint' => $endpoint,
                            'resource_type' => null,
                            'resource_id' => null,
                            'hour' => $hourUtc,
                            'count' => (int) $val,
                        ],
                        ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
                        ['count' => DB::raw('api_usage_hourly.count + '.(int) $val)]
                    );

                    DB::table('api_usage_daily')->upsert(
                        [
                            'organization_id' => $orgId,
                            'token_id' => $tokenId,
                            'method' => $method,
                            'endpoint' => $endpoint,
                            'resource_type' => null,
                            'resource_id' => null,
                            'date' => $dayUtc,
                            'count' => (int) $val,
                        ],
                        ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
                        ['count' => DB::raw('api_usage_daily.count + '.(int) $val)]
                    );

                    $store->forget($k);
                }

                $store->forget($indexKey);
            });
        }
    }
}
