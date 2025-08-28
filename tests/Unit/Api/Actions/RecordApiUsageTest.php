<?php

use App\Api\Actions\RecordApiUsage;
use App\Api\Helpers\UsageCacheKey;
use App\Api\Helpers\UsageDate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

it('indexes counter keys in redis without inserting Array literal', function () {
    Carbon::setTestNow('2025-08-27 14:44:10');

    $pipe = new class
    {
        public array $saddCalls = [];

        public function __call(string $method, array $args): void
        {
            if ($method === 'sadd') {
                $this->saddCalls[] = $args;
            }
        }
    };

    $redis = new class($pipe)
    {
        public function __construct(public $pipe) {}

        public function pipeline(callable $callback): void
        {
            $callback($this->pipe);
        }
    };

    $store = new class($redis)
    {
        public function __construct(private $redis) {}

        public function getStore(): object
        {
            return new class($this->redis)
            {
                public function __construct(private $redis) {}

                public function connection(): object
                {
                    return $this->redis;
                }
            };
        }
    };

    Cache::shouldReceive('store')->andReturn($store);

    (new RecordApiUsage)->handle('1', '2', 'get', '/foo');

    $bucket = UsageDate::formatBucket(UsageDate::now());
    $expectedIndex = UsageCacheKey::index($bucket);
    $expectedCounter = UsageCacheKey::counter(
        bucket: $bucket,
        orgId: '1',
        tokenId: '2',
        method: 'GET',
        endpoint: '/foo',
    );

    expect($pipe->saddCalls)->toHaveCount(1)
        ->and($pipe->saddCalls[0])->toBe([$expectedIndex, $expectedCounter]);

    Carbon::setTestNow();
});
