<?php

namespace App\Filament\Widgets\Activity;

class DashboardApiTimelineChart extends ApiActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'API Activity Trend (This month)';

    protected function getFilters(): ?array
    {
        return null;
    }
}
