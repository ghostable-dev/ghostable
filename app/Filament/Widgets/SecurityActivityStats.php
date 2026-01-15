<?php

namespace App\Filament\Widgets;

use App\Core\Models\Activity;
use App\Filament\Resources\Core\SecurityActivity\SecurityActivityResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SecurityActivityStats extends BaseWidget
{
    protected function getStats(): array
    {
        $now = now()->timezone(timezone());

        $query = SecurityActivityResource::applySecurityScopeTo(Activity::query());

        return [
            Stat::make('Security Today', (clone $query)->whereDate('created_at', $now)->count()),
            Stat::make('Security This Week', (clone $query)->whereBetween('created_at', [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ])->count()),
            Stat::make('Security This Month', (clone $query)->whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])->count()),
        ];
    }
}
