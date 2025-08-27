<?php

namespace App\Api\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpsertApiUsageHourly
{
    public function handle(
        string $organizationId,
        string $tokenId,
        string $method,
        string $endpoint,
        Carbon $hour,
        int $count,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): void {
        DB::table('api_usage_hourly')->upsert(
            [
                'organization_id' => $organizationId,
                'token_id' => $tokenId,
                'method' => $method,
                'endpoint' => $endpoint,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'hour' => $hour,
                'count' => $count,
            ],
            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'hour'],
            ['count' => DB::raw('api_usage_hourly.count + '.$count)]
        );
    }
}
