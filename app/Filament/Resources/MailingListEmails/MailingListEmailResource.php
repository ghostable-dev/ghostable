<?php

namespace App\Filament\Resources\MailingListEmails;

use App\Account\Models\MailingListEmail;
use App\Filament\Resources\MailingListEmails\Pages\ListMailingListEmails;
use App\Filament\Resources\MailingListEmails\Pages\ViewMailingListEmail;
use App\Filament\Resources\MailingListEmails\Tables\MailingListEmailsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MailingListEmailResource extends Resource
{
    protected static ?string $model = MailingListEmail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $recordTitleAttribute = 'email';

    public static function table(Table $table): Table
    {
        return MailingListEmailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMailingListEmails::route('/'),
            'view' => ViewMailingListEmail::route('/{record}'),
        ];
    }
}
