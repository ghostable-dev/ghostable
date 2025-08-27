<?php

namespace App\Usage\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FoldUsageCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $store = Cache::store();
        $keys = $store->pull('usage:keys') ?? [];

        foreach ($keys as $key) {
            $count = (int) $store->get($key);
            $parts = explode(':', $key);

            if (count($parts) !== 5) {
                $store->forget($key);

                continue;
            }

            [, , $token, $endpoint, $timestamp] = $parts;

            try {
                $minute = Carbon::createFromFormat('YmdHi', $timestamp, 'UTC');
            } catch (\Exception $e) {
                $store->forget($key);

                continue;
            }

            $hour = $minute->copy()->startOfHour();
            $day = $minute->copy()->startOfDay();

            DB::table('usage_hourly')->upsert([
                'token' => $token,
                'endpoint' => $endpoint,
                'hour' => $hour,
                'count' => $count,
            ], ['token', 'endpoint', 'hour'], ['count' => DB::raw('usage_hourly.count + '.$count)]);

            DB::table('usage_daily')->upsert([
                'token' => $token,
                'endpoint' => $endpoint,
                'date' => $day,
                'count' => $count,
            ], ['token', 'endpoint', 'date'], ['count' => DB::raw('usage_daily.count + '.$count)]);

            $store->forget($key);
        }
    }
}
