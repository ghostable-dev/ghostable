<?php

namespace App\Filament\Resources\MailingListEmails\Tables;

use App\Account\Enums\MailingListEmailSource;
use App\Filament\Columns\DateColumn;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MailingListEmailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->color(function ($state) {
                        $source = $state instanceof MailingListEmailSource
                            ? $state
                            : (is_string($state) ? MailingListEmailSource::tryFrom($state) : null);

                        return match ($source) {
                            MailingListEmailSource::BLOG => Color::Blue,
                            MailingListEmailSource::INTEGRATION => Color::Green,
                            default => Color::Gray,
                        };
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof MailingListEmailSource) {
                            return $state->label();
                        }

                        if (is_string($state)) {
                            $enum = MailingListEmailSource::tryFrom($state);

                            return $enum?->label() ?? ucwords(str_replace('_', ' ', $state));
                        }

                        return $state;
                    })
                    ->sortable(),
                DateColumn::make('created_at')
                    ->sortable(),
                DateColumn::make('deleted_at')
                    ->label('Unsubscribed At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
