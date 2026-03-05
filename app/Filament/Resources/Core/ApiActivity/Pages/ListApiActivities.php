<?php

namespace App\Filament\Resources\Core\ApiActivity\Pages;

use App\Filament\Resources\Core\ApiActivity\ApiActivityResource;
use App\Filament\Widgets\Activity\ApiActivityTimelineChart;
use App\Filament\Widgets\ApiActivitySourceStats;
use App\Filament\Widgets\ApiUsageStats;
use Filament\Resources\Pages\ListRecords;

class ListApiActivities extends ListRecords
{
    protected static string $resource = ApiActivityResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ApiActivityTimelineChart::class,
            ApiUsageStats::class,
            ApiActivitySourceStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
