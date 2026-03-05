<?php

namespace App\Filament\Widgets;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\ApiActivity\ApiActivityResource;
use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApiActivitySourceStats extends BaseWidget
{
    protected static bool $isDiscovered = false;

    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $query = ApiActivityResource::applyApiScopeTo(Activity::query());
        $rangeQuery = $this->applyActivityDateRange(clone $query);
        $label = $this->activityRangeLabel();

        return [
            Stat::make("API Activity ({$label})", (clone $rangeQuery)->count()),
            Stat::make("CLI ({$label})", (clone $rangeQuery)
                ->where('properties->source', 'cli')
                ->count()),
            Stat::make("Desktop ({$label})", (clone $rangeQuery)
                ->where('properties->source', 'desktop')
                ->count()),
        ];
    }
}
