<?php

namespace App\Api\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $attrs = [
            'organization_id' => $organizationId,
            'token_id' => $tokenId,
            'method' => $method,
            'endpoint' => $endpoint,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'date' => $day,
        ];

        DB::transaction(function () use ($attrs, $count) {
            $query = DB::table('api_usage_daily')->where($attrs);
            if ($query->increment('count', $count, ['updated_at' => now()]) === 0) {
                DB::table('api_usage_daily')->insert($attrs + [
                    'count' => $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
