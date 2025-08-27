<?php

namespace App\Usage;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class UsageRecorder
{
    private string $indexKey = 'usage:keys';
    private int $ttl;

    public function __construct(?int $ttl = null)
    {
        $this->ttl = $ttl ?? config('usage.cache_ttl');
    }

    public function record(string $tokenId, string $endpoint): void
    {
        $store = Cache::store();
        $now = Carbon::now('UTC');
        $key = sprintf('usage:minute:%s:%s:%s', $tokenId, $endpoint, $now->format('YmdHi'));

        $store->add($key, 0, now()->addSeconds($this->ttl));
        $store->increment($key);

        $keys = $store->get($this->indexKey, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            $store->put($this->indexKey, $keys, now()->addSeconds($this->ttl));
        }
    }
}

