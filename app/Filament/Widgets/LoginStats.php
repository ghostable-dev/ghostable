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

        $rangeQuery = User::query()
            ->whereNotNull('last_login')
            ->whereBetween('last_login', [
                $now->copy()->startOfMonth()->timezone('UTC'),
                $now->copy()->timezone('UTC'),
            ]);

        return [
            Stat::make('Logins (This month)', (clone $rangeQuery)->count()),
            Stat::make('Verified Logins (This month)', (clone $rangeQuery)->whereNotNull('email_verified_at')->count()),
            Stat::make('2FA Logins (This month)', (clone $rangeQuery)->whereNotNull('two_factor_confirmed_at')->count()),
        ];
    }
}
