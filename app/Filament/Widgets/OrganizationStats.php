<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use App\Organization\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStats extends BaseWidget
{
    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $rangeQuery = $this->applyActivityDateRange(Organization::query());
        $label = $this->activityRangeLabel();

        return [
            Stat::make("Organizations ({$label})", (clone $rangeQuery)->count()),
            Stat::make("Owners ({$label})", (clone $rangeQuery)->whereNotNull('owner_id')->distinct('owner_id')->count('owner_id')),
            Stat::make("Without Owner ({$label})", (clone $rangeQuery)->whereNull('owner_id')->count()),
        ];
    }
}
