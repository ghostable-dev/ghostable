<?php

namespace App\Filament\Widgets;

use App\Api\Models\ApiUsageDaily;
use App\Api\Models\ApiUsageHourly;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApiUsageStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        return [
            Stat::make('API Calls This Hour', ApiUsageHourly::where('hour', $now->startOfHour())->sum('count')),
            Stat::make('API Calls Today', ApiUsageDaily::whereDate('date', $now->copy())->sum('count')),
            Stat::make('API Calls This Month', ApiUsageDaily::whereBetween('date', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->sum('count')),
        ];
    }
}
