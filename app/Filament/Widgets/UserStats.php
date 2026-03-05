<?php

namespace App\Filament\Widgets;

use App\Account\Models\User;
use App\Filament\Widgets\Activity\Concerns\InteractsWithActivityRange;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserStats extends BaseWidget
{
    use InteractsWithActivityRange;

    protected function getStats(): array
    {
        $rangeQuery = $this->applyActivityDateRange(User::query());
        $label = $this->activityRangeLabel();

        return [
            Stat::make("Users ({$label})", (clone $rangeQuery)->count()),
            Stat::make("Verified ({$label})", (clone $rangeQuery)->whereNotNull('email_verified_at')->count()),
            Stat::make("Unverified ({$label})", (clone $rangeQuery)->whereNull('email_verified_at')->count()),
        ];
    }
}
