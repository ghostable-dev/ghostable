<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Columns\DateColumn;
use App\Filament\Resources\Organizations\OrganizationResource;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrganizationsRelationshipManager extends RelationManager
{
    protected static ?string $title = 'Organizations';

    protected static string $relationship = 'organizations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->url(function ($record) {
                    return OrganizationResource::getUrl('view', [
                        'record' => $record->organization_id,
                    ]);
                }),
                IconColumn::make('owner')
                    ->boolean(fn ($state, $record) => $state->owner_id === $state->user_id)
                    ->default(false)
                    ->label('Is Owner?'),
                TextColumn::make('role')->badge(),
                DateColumn::make('created_at'),
            ]);
    }
}
