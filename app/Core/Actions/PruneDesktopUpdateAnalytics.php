<?php

declare(strict_types=1);

namespace App\Core\Actions;

use App\Core\Models\DesktopUpdateEvent;
use Illuminate\Support\Facades\Schema;

final class PruneDesktopUpdateAnalytics
{
    /**
     * @return array{anonymized: int, deleted: int}
     */
    public function handle(?int $anonymizeAfterDays = null, ?int $pruneAfterDays = null): array
    {
        if (! Schema::hasTable('desktop_update_events')) {
            return [
                'anonymized' => 0,
                'deleted' => 0,
            ];
        }

        app(FoldDesktopUpdateAnalytics::class)->handle();

        $anonymizeAfterDays = max(1, $anonymizeAfterDays ?? (int) config('desktop-updates.tracking.anonymize_after_days', 7));
        $pruneAfterDays = max($anonymizeAfterDays, $pruneAfterDays ?? (int) config('desktop-updates.tracking.prune_after_days', 90));

        $anonymized = DesktopUpdateEvent::query()
            ->whereNotNull('rolled_up_at')
            ->whereNotNull('ip_address')
            ->whereNull('ip_anonymized_at')
            ->where('created_at', '<', now()->subDays($anonymizeAfterDays))
            ->update([
                'ip_address' => null,
                'ip_anonymized_at' => now(),
                'updated_at' => now(),
            ]);

        $deleted = DesktopUpdateEvent::query()
            ->whereNotNull('rolled_up_at')
            ->where('created_at', '<', now()->subDays($pruneAfterDays))
            ->delete();

        return [
            'anonymized' => $anonymized,
            'deleted' => $deleted,
        ];
    }
}
