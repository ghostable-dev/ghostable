<?php

namespace App\Filament\Widgets;

use App\Core\Models\Activity;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActivityStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        $query = Activity::query()->where('log_name', '!=', 'notifications');

        return [
            Stat::make('Activity Today', (clone $query)->whereDate('created_at', $now)->count()),
            Stat::make('Activity This Week', (clone $query)->whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Activity This Month', (clone $query)->whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
