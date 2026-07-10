<?php

namespace App\Filament\Resources\Licenses\RelationManagers;

use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\Users\UserResource;
use App\Licensing\Models\LicenseActivation;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenseActivationsRelationManager extends RelationManager
{
    protected static ?string $title = 'Activations';

    protected static string $relationship = 'activations';

    protected static ?string $recordTitleAttribute = 'machine_name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('machine_name')
                    ->label('Machine')
                    ->default('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->default('N/A')
                    ->searchable()
                    ->url(fn (LicenseActivation $record): ?string => $record->user
                        ? UserResource::getUrl('view', ['record' => $record->user->getKey()])
                        : null),
                TextColumn::make('device.name')
                    ->label('Device')
                    ->default('N/A')
                    ->searchable()
                    ->url(fn (LicenseActivation $record): ?string => $record->device
                        ? DeviceResource::getUrl('view', ['record' => $record->device->getKey()])
                        : null)
                    ->toggleable(),
                TextColumn::make('status')
                    ->state(fn (LicenseActivation $record): string => $record->status())
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('platform')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('app_version')
                    ->label('App Version')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('last_validated_at')
                    ->label('Last Validated')
                    ->formatStateUsing(fn ($state): string => $state?->timezone(timezone())->format(DT_FORMAT) ?? 'N/A')
                    ->sortable(),
                TextColumn::make('deactivated_at')
                    ->label('Deactivated')
                    ->formatStateUsing(fn ($state): string => $state?->timezone(timezone())->format(DT_FORMAT) ?? 'N/A')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('machine_fingerprint_hash')
                    ->label('Fingerprint Hash')
                    ->limit(18)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state): string => $state->timezone(timezone())->format(DT_FORMAT))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('active')
                    ->nullable()
                    ->queries(
                        true: function (Builder $query): Builder {
                            $query->whereNull('deactivated_at');

                            return $query;
                        },
                        false: function (Builder $query): Builder {
                            $query->whereNotNull('deactivated_at');

                            return $query;
                        },
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('platform')
                    ->options(fn (): array => LicenseActivation::query()
                        ->whereNotNull('platform')
                        ->orderBy('platform')
                        ->pluck('platform', 'platform')
                        ->all()),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
