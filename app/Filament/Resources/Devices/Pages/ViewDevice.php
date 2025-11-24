<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Filament\Components\DateEntry;
use App\Filament\Resources\Devices\DeviceResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Device Details')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('platform'),
                    TextEntry::make('app_version')->label('App Version'),
                    TextEntry::make('active')->boolean(),
                    DateEntry::make('last_seen_at')->label('Last Seen'),
                    DateEntry::make('revoked_at')->label('Revoked At'),
                ])->columnSpanFull(),
                Section::make('Keys')->schema([
                    TextEntry::make('public_key')->label('Public Key')->copyable(),
                    TextEntry::make('public_signing_key')->label('Public Signing Key')->copyable(),
                ])->columnSpanFull(),
                Section::make('User')->schema([
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('user.email')->label('Email'),
                ])->columnSpanFull(),
                Section::make('Timestamps')->schema([
                    DateEntry::make('created_at')->label('Created At'),
                    DateEntry::make('updated_at')->label('Updated At'),
                ])->columnSpanFull(),
            ]);
    }
}
