<?php

namespace App\Filament\Widgets;

use App\Core\Models\Inquiry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InquiryStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        return [
            Stat::make('Inquiries Today', Inquiry::whereDate('created_at', $now)->count()),
            Stat::make('Inquiries This Week', Inquiry::whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Inquiries This Month', Inquiry::whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
