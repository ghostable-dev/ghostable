<?php

namespace App\Filament\Resources\Licenses;

use App\Filament\Resources\Licenses\Pages\ListLicenses;
use App\Filament\Resources\Licenses\Pages\ViewLicense;
use App\Filament\Resources\Licenses\RelationManagers\LicenseActivationsRelationManager;
use App\Filament\Resources\Licenses\RelationManagers\LicenseEventsRelationManager;
use App\Filament\Resources\Licenses\Schemas\LicenseInfolist;
use App\Filament\Resources\Licenses\Tables\LicensesTable;
use App\Licensing\Models\License;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Licensing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static ?string $navigationLabel = 'Licenses';

    protected static ?string $recordTitleAttribute = 'purchaser_email';

    public static function infolist(Schema $schema): Schema
    {
        return LicenseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LicensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LicenseActivationsRelationManager::class,
            LicenseEventsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'view' => ViewLicense::route('/{record}'),
        ];
    }
}
