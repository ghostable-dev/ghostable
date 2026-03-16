<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Core\Actions\DesktopUpdateMetrics;
use App\Core\Enums\DesktopUpdateEventType;
use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DesktopDownloadStats extends BaseWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '30s';

    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $metrics = app(DesktopUpdateMetrics::class);
        $label = $this->activityRangeLabel();

        return [
            Stat::make("Appcast Checks ({$label})", $metrics->count(DesktopUpdateEventType::AppcastChecked, $this->range)),
            Stat::make("Downloads ({$label})", $metrics->count(DesktopUpdateEventType::DownloadRedirected, $this->range)),
            Stat::make("Successful Installs ({$label})", $metrics->count(DesktopUpdateEventType::UpdateInstalled, $this->range)),
            Stat::make(
                "Attributed Installs ({$label})",
                $metrics->count(DesktopUpdateEventType::UpdateInstalled, $this->range, attributedOnly: true),
            ),
        ];
    }
}
