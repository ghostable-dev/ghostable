<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Core\Actions\FoldDesktopUpdateAnalytics;
use Illuminate\Console\Command;

final class FoldDesktopUpdateAnalyticsCommand extends Command
{
    protected $signature = 'desktop:update-analytics:fold';

    protected $description = 'Fold desktop analytics events into daily rollups.';

    public function handle(FoldDesktopUpdateAnalytics $foldDesktopUpdateAnalytics): int
    {
        $processed = $foldDesktopUpdateAnalytics->handle();

        $this->info(sprintf('Folded %d desktop analytics event(s).', $processed));

        return self::SUCCESS;
    }
}
