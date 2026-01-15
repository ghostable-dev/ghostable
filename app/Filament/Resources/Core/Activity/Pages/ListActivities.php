<?php

namespace App\Filament\Resources\Core\Activity\Pages;

use App\Filament\Resources\Core\Activity\ActivityResource;
use App\Filament\Widgets\ActivityStats;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ActivityStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
