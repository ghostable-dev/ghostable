<?php

namespace App\Filament\Resources\Devices\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('platform')
                    ->label('Platform')
                    ->badge()
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label('App Version')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->formatStateUsing(fn ($state) => $state?->timezone(timezone())->format(DT_FORMAT) ?? '—')
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label('Revoked At')
                    ->formatStateUsing(fn ($state) => $state?->timezone(timezone())->format(DT_FORMAT) ?? '—')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->formatStateUsing(fn ($state) => $state->timezone(timezone())->format(DT_FORMAT))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }
}
