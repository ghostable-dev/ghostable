<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\Activity\DashboardActivityTimelineChart;
use App\Filament\Widgets\Activity\DashboardApiTimelineChart;
use App\Filament\Widgets\DashboardOverviewStats;

it('uses a focused set of widgets on the main dashboard', function (): void {
    $dashboard = app(Dashboard::class);

    expect($dashboard->getWidgets())->toBe([
        DashboardOverviewStats::class,
        DashboardActivityTimelineChart::class,
        DashboardApiTimelineChart::class,
    ]);
});
