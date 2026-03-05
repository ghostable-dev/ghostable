<?php

use App\Filament\Widgets\Activity\DashboardActivityTimelineChart;
use App\Filament\Widgets\Activity\DashboardApiTimelineChart;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('hides range filters for the main dashboard activity chart', function (): void {
    $widget = new class extends DashboardActivityTimelineChart
    {
        public function exposedFilters(): ?array
        {
            return $this->getFilters();
        }
    };

    expect($widget->filter)->toBe('this_month')
        ->and($widget->exposedFilters())->toBeNull();
});

it('hides range filters for the main dashboard api chart', function (): void {
    $widget = new class extends DashboardApiTimelineChart
    {
        public function exposedFilters(): ?array
        {
            return $this->getFilters();
        }
    };

    expect($widget->filter)->toBe('this_month')
        ->and($widget->exposedFilters())->toBeNull();
});
