<?php

namespace App\Api\Entities;

use Spatie\LaravelData\Data;

class UsageCacheAggregateData extends Data
{
    public function __construct(
        public string $bucket,
        public string $orgId,
        public string $tokenId,
        public string $method,
        public string $endpoint,
    ) {}
}
