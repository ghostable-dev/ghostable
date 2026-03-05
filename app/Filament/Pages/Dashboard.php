<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Activity\DashboardActivityTimelineChart;
use App\Filament\Widgets\Activity\DashboardApiTimelineChart;
use App\Filament\Widgets\DashboardOverviewStats;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            DashboardOverviewStats::class,
            DashboardActivityTimelineChart::class,
            DashboardApiTimelineChart::class,
        ];
    }
}
