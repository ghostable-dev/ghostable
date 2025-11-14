<?php

namespace App\Api\Usage\Actions;

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
    ): void {
        $attrs = [
            'organization_id' => $organizationId,
            'token_id' => $tokenId,
            'method' => $method,
            'endpoint' => $endpoint,
            'hour' => $hour,
        ];

        DB::transaction(function () use ($attrs, $count) {
            $query = DB::table('api_usage_hourly')->where($attrs);
            if ($query->increment('count', $count, ['updated_at' => now()]) === 0) {
                DB::table('api_usage_hourly')->insert($attrs + [
                    'count' => $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
