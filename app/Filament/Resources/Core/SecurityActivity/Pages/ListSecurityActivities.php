<?php

namespace App\Filament\Resources\Core\SecurityActivity\Pages;

use App\Filament\Resources\Core\SecurityActivity\SecurityActivityResource;
use App\Filament\Widgets\Activity\SecurityActivityTimelineChart;
use App\Filament\Widgets\SecurityActivityStats;
use Filament\Resources\Pages\ListRecords;

class ListSecurityActivities extends ListRecords
{
    protected static string $resource = SecurityActivityResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            SecurityActivityTimelineChart::class,
            SecurityActivityStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
