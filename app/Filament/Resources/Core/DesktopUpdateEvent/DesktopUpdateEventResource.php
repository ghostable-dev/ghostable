<?php

declare(strict_types=1);

namespace App\Filament\Resources\Core\DesktopUpdateEvent;

use App\Account\Models\User;
use App\Core\Enums\DesktopUpdateEventType;
use App\Core\Enums\DesktopUpdateSource;
use App\Core\Models\DesktopUpdateEvent;
use App\Crypto\Models\Device;
use App\Filament\Columns\DateColumn;
use App\Filament\Resources\Core\DesktopUpdateEvent\Pages\ListDesktopUpdateEvents;
use App\Filament\Resources\Devices\DeviceResource;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DesktopUpdateEventResource extends Resource
{
    protected static ?string $model = DesktopUpdateEvent::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    protected static ?string $navigationLabel = 'Desktop Updates';

    protected static ?string $label = 'Desktop Update Event';

    public static ?string $slug = 'desktop-updates';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'DESC')
            ->columns([
                DateColumn::make('created_at')
                    ->label('Occurred On')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->colors([
                        'secondary',
                        'warning' => static fn (DesktopUpdateEventType|string|null $state): bool => static::eventTypeValue($state) === DesktopUpdateEventType::AppcastChecked->value,
                        'success' => static fn (DesktopUpdateEventType|string|null $state): bool => static::eventTypeValue($state) === DesktopUpdateEventType::UpdateInstalled->value,
                        'danger' => static fn (DesktopUpdateEventType|string|null $state): bool => static::eventTypeValue($state) === DesktopUpdateEventType::UpdateFailed->value,
                    ])
                    ->formatStateUsing(fn (DesktopUpdateEventType|string|null $state): string => static::eventTypeLabel($state))
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->colors([
                        'secondary',
                        'warning' => static fn (DesktopUpdateSource|string|null $state): bool => static::sourceValue($state) === DesktopUpdateSource::LatestDownload->value,
                        'success' => static fn (DesktopUpdateSource|string|null $state): bool => static::sourceValue($state) === DesktopUpdateSource::Sparkle->value,
                    ])
                    ->formatStateUsing(fn (DesktopUpdateSource|string|null $state): string => static::sourceLabel($state)),
                TextColumn::make('channel')
                    ->badge()
                    ->colors([
                        'secondary',
                        'warning' => static fn (?string $state): bool => $state === 'beta',
                    ]),
                TextColumn::make('release_version')
                    ->label('Release')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('current_version')
                    ->label('Current')
                    ->toggleable(),
                TextColumn::make('from_version')
                    ->label('From')
                    ->toggleable(),
                TextColumn::make('attributed')
                    ->label('Attribution')
                    ->state(fn (DesktopUpdateEvent $record): string => $record->attributed ? 'Attributed' : 'Anonymous')
                    ->badge()
                    ->colors([
                        'secondary' => static fn (string $state): bool => $state === 'Anonymous',
                        'success' => static fn (string $state): bool => $state === 'Attributed',
                    ]),
                TextColumn::make('user.email')
                    ->label('User')
                    ->default('Anonymous')
                    ->url(fn (DesktopUpdateEvent $record): ?string => $record->user
                        ? UserResource::getUrl('view', ['record' => $record->user->getKey()])
                        : null)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('device.name')
                    ->label('Device')
                    ->default('N/A')
                    ->url(fn (DesktopUpdateEvent $record): ?string => $record->device
                        ? DeviceResource::getUrl('view', ['record' => $record->device->getKey()])
                        : null)
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ip_hash')
                    ->label('IP Hash')
                    ->limit(18)
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->default('Anonymized')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metadata')
                    ->label('Metadata')
                    ->getStateUsing(function (DesktopUpdateEvent $record): string {
                        return '<code class="bg-gray-100 p-2 text-xs rounded">'.e(json_encode($record->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</code>';
                    })
                    ->html()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Event')
                    ->options(DesktopUpdateEventType::options())
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'event_type' => $data['value'] ?? null,
                    ])),
                SelectFilter::make('source')
                    ->label('Source')
                    ->options(DesktopUpdateSource::options())
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'source' => $data['value'] ?? null,
                    ])),
                SelectFilter::make('channel')
                    ->label('Channel')
                    ->options([
                        'stable' => 'Stable',
                        'beta' => 'Beta',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'channel' => $data['value'] ?? null,
                    ])),
                SelectFilter::make('release_version')
                    ->label('Version')
                    ->options(fn (): array => DesktopUpdateEvent::query()
                        ->whereNotNull('release_version')
                        ->orderByDesc('release_version')
                        ->pluck('release_version', 'release_version')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'release_version' => $data['value'] ?? null,
                    ])),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->options(fn (): array => User::query()->orderBy('email')->pluck('email', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'user_id' => $data['value'] ?? null,
                    ])),
                SelectFilter::make('device_id')
                    ->label('Device')
                    ->options(fn (): array => Device::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'device_id' => $data['value'] ?? null,
                    ])),
                TernaryFilter::make('attributed')
                    ->label('Attributed')
                    ->nullable()
                    ->queries(
                        true: fn (Builder $query): Builder => static::applyFiltersTo($query, ['attributed' => true]),
                        false: fn (Builder $query): Builder => static::applyFiltersTo($query, ['attributed' => false]),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('occurred_between')
                    ->label('Occurred Between')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyFiltersTo($query, [
                        'from' => $data['from'] ?? null,
                        'until' => $data['until'] ?? null,
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesktopUpdateEvents::route('/'),
        ];
    }

    public static function applyFiltersTo(Builder $query, array $filters = []): Builder
    {
        return $query
            ->when($filters['event_type'] ?? null, fn (Builder $query, string $value): Builder => $query->where('event_type', $value))
            ->when($filters['source'] ?? null, fn (Builder $query, string $value): Builder => $query->where('source', $value))
            ->when($filters['channel'] ?? null, fn (Builder $query, string $value): Builder => $query->where('channel', $value))
            ->when($filters['release_version'] ?? null, fn (Builder $query, string $value): Builder => $query->where('release_version', $value))
            ->when($filters['user_id'] ?? null, fn (Builder $query, string $value): Builder => $query->where('user_id', $value))
            ->when($filters['device_id'] ?? null, fn (Builder $query, string $value): Builder => $query->where('device_id', $value))
            ->when(array_key_exists('attributed', $filters), fn (Builder $query): Builder => $query->where('attributed', (bool) $filters['attributed']))
            ->when($filters['from'] ?? null, function (Builder $query, string $value): Builder {
                $from = Carbon::parse($value, timezone())->startOfDay()->timezone('UTC');

                return $query->where('created_at', '>=', $from);
            })
            ->when($filters['until'] ?? null, function (Builder $query, string $value): Builder {
                $until = Carbon::parse($value, timezone())->endOfDay()->timezone('UTC');

                return $query->where('created_at', '<=', $until);
            });
    }

    private static function eventTypeLabel(DesktopUpdateEventType|string|null $state): string
    {
        $eventType = $state instanceof DesktopUpdateEventType ? $state : DesktopUpdateEventType::tryFrom((string) $state);

        return $eventType?->label() ?? ucfirst(str_replace('_', ' ', (string) $state));
    }

    private static function eventTypeValue(DesktopUpdateEventType|string|null $state): ?string
    {
        return $state instanceof DesktopUpdateEventType ? $state->value : (is_string($state) ? $state : null);
    }

    private static function sourceLabel(DesktopUpdateSource|string|null $state): string
    {
        $source = $state instanceof DesktopUpdateSource ? $state : DesktopUpdateSource::tryFrom((string) $state);

        return $source?->label() ?? ucfirst(str_replace('_', ' ', (string) $state));
    }

    private static function sourceValue(DesktopUpdateSource|string|null $state): ?string
    {
        return $state instanceof DesktopUpdateSource ? $state->value : (is_string($state) ? $state : null);
    }
}
