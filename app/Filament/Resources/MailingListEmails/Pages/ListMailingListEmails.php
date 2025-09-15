<?php

namespace App\Filament\Resources\MailingListEmails\Pages;

use App\Filament\Resources\MailingListEmails\MailingListEmailResource;
use App\Filament\Widgets\MailingListEmailStats;
use Filament\Resources\Pages\ListRecords;

class ListMailingListEmails extends ListRecords
{
    protected static string $resource = MailingListEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            MailingListEmailStats::make(),
        ];
    }
}
