<?php

namespace App\Filament\Widgets;

use App\Api\Usage\Models\ApiUsageDaily;
use App\Api\Usage\Models\ApiUsageHourly;
use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class ApiUsageStats extends BaseWidget
{
    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $label = $this->activityRangeLabel();

        return [
            Stat::make("API Calls ({$label})", $this->apiUsageQuery()->sum('count')),
            Stat::make('Unique Endpoints', $this->apiUsageQuery()->distinct('endpoint')->count('endpoint')),
            Stat::make('Tokens Used', $this->apiUsageQuery()->distinct('token_id')->count('token_id')),
        ];
    }

    protected function apiUsageQuery(): Builder
    {
        $range = $this->normalizeActivityRange($this->range);
        $now = now()->timezone(timezone());

        if ($range === 'today') {
            $query = ApiUsageHourly::query();

            return $query->whereBetween('hour', [
                $now->copy()->startOfDay()->timezone('UTC'),
                $now->copy()->timezone('UTC'),
            ]);
        }

        $query = ApiUsageDaily::query();

        return match ($range) {
            'this_week' => $query->whereBetween('date', [
                $now->copy()->startOfWeek()->toDateString(),
                $now->copy()->toDateString(),
            ]),
            'this_month' => $query->whereBetween('date', [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->toDateString(),
            ]),
            default => $query,
        };
    }
}
