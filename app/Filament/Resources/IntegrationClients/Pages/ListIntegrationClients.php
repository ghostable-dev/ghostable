<?php

namespace App\Filament\Resources\IntegrationClients\Pages;

use App\Filament\Resources\IntegrationClients\IntegrationClientResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListIntegrationClients extends ListRecords
{
    protected static string $resource = IntegrationClientResource::class;

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
