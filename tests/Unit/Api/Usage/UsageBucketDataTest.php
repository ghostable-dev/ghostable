<?php

use App\Api\Usage\Entities\UsageBucketData;

it('builds usage bucket data from bucket and endpoint', function () {
    $bucket = '20250827T1445';
    $endpoint = str_repeat('a', 200);

    $data = UsageBucketData::fromBucket($bucket, $endpoint);

    expect(strlen($data->endpoint))->toBe(191)
        ->and($data->minuteUtc->toIso8601String())->toBe('2025-08-27T14:45:00+00:00')
        ->and($data->hourUtc->toIso8601String())->toBe('2025-08-27T14:00:00+00:00')
        ->and($data->dayUtc->toIso8601String())->toBe('2025-08-27T00:00:00+00:00');
});
