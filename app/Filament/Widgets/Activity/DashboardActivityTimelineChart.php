<?php

namespace App\Filament\Widgets\Activity;

class DashboardActivityTimelineChart extends AllActivityTimelineChart
{
    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Activity Trend (This month)';

    protected function getFilters(): ?array
    {
        return null;
    }
}
