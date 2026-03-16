<?php

declare(strict_types=1);

namespace App\Filament\Resources\Core\DesktopUpdateEvent\Pages;

use App\Filament\Resources\Core\DesktopUpdateEvent\DesktopUpdateEventResource;
use App\Filament\Widgets\Activity\DesktopDownloadTimelineChart;
use App\Filament\Widgets\DesktopDownloadStats;
use Filament\Resources\Pages\ListRecords;

class ListDesktopUpdateEvents extends ListRecords
{
    protected static string $resource = DesktopUpdateEventResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DesktopDownloadTimelineChart::class,
            DesktopDownloadStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
