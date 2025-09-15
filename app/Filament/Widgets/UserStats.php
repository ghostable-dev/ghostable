<?php

namespace App\Filament\Widgets;

use App\Account\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        return [
            Stat::make('Users Today', User::whereDate('created_at', $now)->count()),
            Stat::make('Users This Week', User::whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Users This Month', User::whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
