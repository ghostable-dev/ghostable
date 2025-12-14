<?php

namespace App\Filament\Widgets;

use App\Account\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoginStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        return [
            Stat::make('Logins Today', User::whereNotNull('last_login')->whereDate('last_login', $now)->count()),
            Stat::make('Logins This Week', User::whereNotNull('last_login')->whereBetween('last_login', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Logins This Month', User::whereNotNull('last_login')->whereBetween('last_login', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
