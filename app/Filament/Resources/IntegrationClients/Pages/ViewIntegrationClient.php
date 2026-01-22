<?php

namespace App\Filament\Resources\IntegrationClients\Pages;

use App\Filament\Components\DateEntry;
use App\Filament\Resources\IntegrationClients\IntegrationClientResource;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewIntegrationClient extends ViewRecord
{
    protected static string $resource = IntegrationClientResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('key'),
                    TextEntry::make('ownerOrganization.name')->label('Owner'),
                    TextEntry::make('publish_status')->label('Publish status'),
                    TextEntry::make('status'),
                ])->columnSpanFull(),
                Section::make('OAuth')->schema([
                    TextEntry::make('client_id')->label('Client ID'),
                    TextEntry::make('redirect_uris')
                        ->label('Redirect URIs')
                        ->formatStateUsing(fn ($state) => $state ? implode(', ', (array) $state) : 'None'),
                    TextEntry::make('default_scopes')
                        ->label('Default scopes')
                        ->formatStateUsing(fn ($state) => $state ? implode(', ', (array) $state) : 'None'),
                ])->columnSpanFull(),
                Section::make('Partner metadata')->schema([
                    TextEntry::make('landing_page_url')
                        ->label('Landing page')
                        ->formatStateUsing(fn ($state) => $state ?: 'None'),
                    TextEntry::make('description')
                        ->formatStateUsing(fn ($state) => $state ?: 'None'),
                    TextEntry::make('logo_path')
                        ->label('Logo path')
                        ->formatStateUsing(fn ($state) => $state ?: 'None'),
                    ImageEntry::make('logo_path')
                        ->label('Logo')
                        ->disk('public')
                        ->visibility('public')
                        ->imageSize(64)
                        ->square(),
                ])->columnSpanFull(),
                Section::make('Timestamps')->schema([
                    DateEntry::make('created_at')->label('Created at'),
                    DateEntry::make('updated_at')->label('Updated at'),
                ])->columnSpanFull(),
            ]);
    }
}
