<?php

namespace App\Filament\Resources\Licenses\RelationManagers;

use App\Licensing\Models\LicenseEvent;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenseEventsRelationManager extends RelationManager
{
    protected static ?string $title = 'Events';

    protected static string $relationship = 'events';

    protected static ?string $recordTitleAttribute = 'type';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Occurred')
                    ->formatStateUsing(fn ($state): string => $state->timezone(timezone())->format(DT_FORMAT))
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('activation.machine_name')
                    ->label('Activation')
                    ->default('N/A')
                    ->searchable(),
                TextColumn::make('metadata')
                    ->getStateUsing(fn (LicenseEvent $record): string => self::formatJson($record->metadata))
                    ->html()
                    ->wrap()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(fn (): array => LicenseEvent::query()
                        ->whereNotNull('type')
                        ->orderBy('type')
                        ->pluck('type', 'type')
                        ->all()),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    private static function formatJson(?array $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        return '<code class="bg-gray-100 p-2 text-xs rounded">'.e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</code>';
    }
}
