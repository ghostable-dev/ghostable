<?php

namespace App\Filament\Widgets;

use App\Account\Models\MailingListEmail;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MailingListEmailStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        return [
            Stat::make('Mailing List Signups Today', MailingListEmail::whereDate('created_at', $now)->count()),
            Stat::make('Mailing List Signups This Week', MailingListEmail::whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Mailing List Signups This Month', MailingListEmail::whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
