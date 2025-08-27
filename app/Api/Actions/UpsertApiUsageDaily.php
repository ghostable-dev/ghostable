<?php

namespace App\Api\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class UpsertApiUsageDaily
{
    public function handle(
        string $organizationId,
        string $tokenId,
        string $method,
        string $endpoint,
        Carbon $day,
        int $count,
        ?string $resourceType = null,
        ?string $resourceId = null,
    ): void {
        DB::table('api_usage_daily')->upsert(
            [
                'organization_id' => $organizationId,
                'token_id' => $tokenId,
                'method' => $method,
                'endpoint' => $endpoint,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'date' => $day,
                'count' => $count,
            ],
            ['organization_id', 'token_id', 'method', 'endpoint', 'resource_type', 'resource_id', 'date'],
            ['count' => DB::raw('api_usage_daily.count + '.$count)]
        );
    }
}
