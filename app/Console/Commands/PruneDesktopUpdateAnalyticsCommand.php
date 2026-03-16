<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Actions\PruneDesktopUpdateAnalytics;
use Illuminate\Console\Command;

final class PruneDesktopUpdateAnalyticsCommand extends Command
{
    protected $signature = 'desktop:update-analytics:prune
        {--anonymize-after-days= : Override the configured anonymize retention window}
        {--prune-after-days= : Override the configured delete retention window}';

    protected $description = 'Anonymize and prune desktop analytics events.';

    public function handle(PruneDesktopUpdateAnalytics $pruneDesktopUpdateAnalytics): int
    {
        $results = $pruneDesktopUpdateAnalytics->handle(
            anonymizeAfterDays: $this->option('anonymize-after-days') !== null
                ? (int) $this->option('anonymize-after-days')
                : null,
            pruneAfterDays: $this->option('prune-after-days') !== null
                ? (int) $this->option('prune-after-days')
                : null,
        );

        $this->info(sprintf(
            'Anonymized %d row(s) and pruned %d row(s).',
            $results['anonymized'],
            $results['deleted'],
        ));

        return self::SUCCESS;
    }
}
