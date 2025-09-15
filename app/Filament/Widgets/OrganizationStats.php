<?php

namespace App\Filament\Widgets;

use App\Organization\Models\Organization;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrganizationStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());
        
        return [
            Stat::make('Organizations Today', Organization::whereDate('created_at', $now)->count()),
            Stat::make('Organizations This Week', Organization::whereBetween('created_at', [
                $now->copy()->startOfWeek(), 
                $now->copy()->endOfWeek()
            ])->count()),
            Stat::make('Organizations This Month', Organization::whereBetween('created_at', [
                $now->copy()->startOfMonth(), 
                $now->copy()->endOfMonth()
            ])->count()),
        ];
    }
}
