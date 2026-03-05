<?php

namespace App\Filament\Widgets;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\Activity\ActivityResource;
use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivityStats extends BaseWidget
{
    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $query = ActivityResource::applyActivityScopeTo(Activity::query());
        $rangeQuery = $this->applyActivityDateRange(clone $query);
        $label = $this->activityRangeLabel();

        return [
            Stat::make("Activity ({$label})", (clone $rangeQuery)->count()),
            Stat::make('Active Actors', (clone $rangeQuery)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id')),
            Stat::make('Event Types', (clone $rangeQuery)->whereNotNull('event')->distinct('event')->count('event')),
        ];
    }
}
