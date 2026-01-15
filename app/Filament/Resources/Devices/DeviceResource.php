<?php

namespace App\Filament\Resources\Devices;

use App\Crypto\Models\Device;
use App\Filament\Resources\Devices\Pages\ListDevices;
use App\Filament\Resources\Devices\Pages\ViewDevice;
use App\Filament\Resources\Devices\Tables\DevicesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Accounts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return DevicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'view' => ViewDevice::route('/{record}'),
        ];
    }
}
