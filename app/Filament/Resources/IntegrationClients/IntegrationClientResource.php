<?php

namespace App\Filament\Resources\IntegrationClients;

use App\Filament\Resources\IntegrationClients\Pages\ListIntegrationClients;
use App\Filament\Resources\IntegrationClients\Pages\ViewIntegrationClient;
use App\Filament\Resources\IntegrationClients\Tables\IntegrationClientsTable;
use App\Integration\Models\IntegrationClient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntegrationClientResource extends Resource
{
    protected static ?string $model = IntegrationClient::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Integrations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCubeTransparent;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return IntegrationClientsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIntegrationClients::route('/'),
            'view' => ViewIntegrationClient::route('/{record}'),
        ];
    }
}
