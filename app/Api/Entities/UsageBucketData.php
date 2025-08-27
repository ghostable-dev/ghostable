<?php

namespace App\Api\Entities;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;

class UsageBucketData extends Data
{
    public function __construct(
        public string $endpoint,
        public Carbon $minuteUtc,
        public Carbon $hourUtc,
        public Carbon $dayUtc,
    ) {}

    public static function fromBucket(string $bucket, string $endpoint): self
    {
        $endpoint = Str::limit($endpoint, 191, '');
        $minuteUtc = Carbon::createFromFormat('Ymd\THi', $bucket, 'UTC')->startOfMinute();
        $hourUtc = $minuteUtc->copy()->startOfHour();
        $dayUtc = $minuteUtc->copy()->startOfDay();

        return new self($endpoint, $minuteUtc, $hourUtc, $dayUtc);
    }
}
