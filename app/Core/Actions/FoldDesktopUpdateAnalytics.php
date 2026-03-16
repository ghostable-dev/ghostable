<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\DesktopUpdateDailyRollup;
use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class FoldDesktopUpdateAnalytics
{
    public function handle(): int
    {
        if (! Schema::hasTable('desktop_update_events') || ! Schema::hasTable('desktop_update_daily_rollups')) {
            return 0;
        }

        $dates = DesktopUpdateEvent::query()
            ->whereNull('rolled_up_at')
            ->selectRaw('DATE(created_at) as event_date')
            ->distinct()
            ->pluck('event_date')
            ->filter()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $processed = 0;

        foreach ($dates as $date) {
            $processed += $this->rebuildDate((string) $date);
        }

        return $processed;
    }

    private function rebuildDate(string $date): int
    {
        $rebuiltAt = now();

        return DB::transaction(function () use ($date, $rebuiltAt): int {
            $aggregates = DB::table('desktop_update_events')
                ->selectRaw("
                    event_type,
                    channel,
                    COALESCE(release_version, '') as release_version,
                    MAX(COALESCE(release_short_version, '')) as release_short_version,
                    attributed,
                    COUNT(*) as total_events,
                    COUNT(DISTINCT ip_hash) as unique_ip_hashes,
                    COUNT(DISTINCT device_id) as unique_devices,
                    COUNT(DISTINCT user_id) as unique_users
                ")
                ->whereDate('created_at', $date)
                ->groupByRaw("event_type, channel, COALESCE(release_version, ''), attributed")
                ->get();

            DesktopUpdateDailyRollup::query()
                ->whereDate('date', $date)
                ->delete();

            if ($aggregates->isNotEmpty()) {
                DB::table('desktop_update_daily_rollups')->insert(
                    $aggregates->map(function (object $aggregate) use ($date, $rebuiltAt): array {
                        return [
                            'date' => $date,
                            'event_type' => (string) $aggregate->event_type,
                            'channel' => (string) $aggregate->channel,
                            'release_version' => (string) $aggregate->release_version,
                            'release_short_version' => (string) $aggregate->release_short_version,
                            'attributed' => (bool) $aggregate->attributed,
                            'total_events' => (int) $aggregate->total_events,
                            'unique_ip_hashes' => (int) $aggregate->unique_ip_hashes,
                            'unique_devices' => (int) $aggregate->unique_devices,
                            'unique_users' => (int) $aggregate->unique_users,
                            'created_at' => $rebuiltAt,
                            'updated_at' => $rebuiltAt,
                        ];
                    })->all(),
                );
            }

            return DesktopUpdateEvent::query()
                ->whereDate('created_at', $date)
                ->whereNull('rolled_up_at')
                ->update([
                    'rolled_up_at' => $rebuiltAt,
                    'updated_at' => $rebuiltAt,
                ]);
        });
    }
}
