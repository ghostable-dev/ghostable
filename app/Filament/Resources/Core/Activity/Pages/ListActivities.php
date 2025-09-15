<?php

namespace App\Filament\Resources\Core\Activity\Pages;

use App\Filament\Resources\Core\Activity\ActivityResource;
use Filament\Resources\Pages\ListRecords;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
