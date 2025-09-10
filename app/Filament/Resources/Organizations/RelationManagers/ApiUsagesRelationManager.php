<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiUsagesRelationManager extends RelationManager
{
    protected static string $relationship = 'apiUsages';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->date(),
                TextColumn::make('method')
                    ->label('Method'),
                TextColumn::make('endpoint')
                    ->label('Endpoint')
                    ->searchable(),
                TextColumn::make('count')
                    ->label('Count'),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([])
            ->bulkActions([]);
    }
}
