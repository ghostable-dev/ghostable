<?php

namespace App\Filament\Resources\Core\ApiActivity;

use App\Account\Models\User;
use App\Core\Models\Activity;
use App\Filament\Columns\DateColumn;
use App\Filament\Resources\Core\ApiActivity\Pages\ListApiActivities;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\CarbonInterface;

class ApiActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?string $navigationLabel = 'API';

    protected static ?string $label = 'API Activity';

    public static ?string $slug = 'api-activity';

    protected static ?int $navigationSort = 3;

    /**
     * @var array<string, string>
     */
    private const SOURCES = [
        'cli' => 'CLI',
        'desktop' => 'Desktop',
        'api' => 'API',
        'deploy-api' => 'Deploy API',
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'DESC')
            ->modifyQueryUsing(function (Builder $query): Builder {
                return static::applyApiScope($query);
            })
            ->columns([
                DateColumn::make('created_at')
                    ->label('Occurred On')
                    ->formatStateUsing(fn (mixed $state): string => static::formatOccurredOn($state))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('api_source')
                    ->label('Source')
                    ->getStateUsing(fn (Activity $activity): string => (string) data_get($activity->properties, 'source', 'unknown'))
                    ->formatStateUsing(fn (string $state): string => static::sourceLabel($state))
                    ->badge()
                    ->color(fn (string $state): string => static::sourceColor($state)),
                TextColumn::make('event')
                    ->badge()
                    ->colors([
                        'success' => static fn ($state): bool => in_array($state, [
                            'created',
                            'push',
                            'deploy',
                            'environment_key_created',
                            'environment_key_reshared',
                            'environment_key_reshare_completed',
                            'mfa_succeeded',
                            'login',
                        ], true),
                        'warning' => static fn ($state): bool => in_array($state, [
                            'updated',
                            'commented',
                            'uncommented',
                            'project_history_viewed',
                            'variable_history_viewed',
                            'history_viewed',
                            'mfa_challenge',
                        ], true),
                        'danger' => static fn ($state): bool => in_array($state, [
                            'deleted',
                            'rollback',
                            'push_force_overwrite',
                            'failed_mfa',
                            'login_failed',
                        ], true),
                        'secondary',
                    ])
                    ->searchable(),
                TextColumn::make('log_name')
                    ->label('Log')
                    ->badge()
                    ->colors([
                        'secondary',
                    ]),
                TextColumn::make('causer.email')
                    ->label('Performed By')
                    ->default('System')
                    ->searchable()
                    ->badge(fn ($state) => $state === 'System')
                    ->url(function (Activity $activity) {
                        return is_null($activity->causer)
                            ? null
                            : UserResource::getUrl('view', [
                                'record' => $activity->causer->id,
                            ]);
                    })
                    ->limit(24)
                    ->colors([
                        'secondary',
                        'warning' => static fn ($state): bool => $state === 'System',
                    ])
                    ->tooltip(fn (Activity $activity) => $activity->causer?->email),
                TextColumn::make('subject.id')
                    ->label('Subject')
                    ->default('N/A')
                    ->colors([
                        'secondary',
                    ])
                    ->searchable()
                    ->description(fn (Activity $activity) => class_basename($activity->subject), position: 'above')
                    ->badge(fn ($state) => $state === 'N/A')
                    ->url(function (Activity $activity) {
                        if (is_null($activity->subject)) {
                            return null;
                        }

                        return match (get_class($activity->subject)) {
                            User::class => UserResource::getUrl('view', ['record' => $activity->subject->id]),
                            default => null,
                        };
                    })
                    ->limit(20)
                    ->tooltip(fn (Activity $activity) => $activity->subject?->id),
                TextColumn::make('description')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('properties')
                    ->getStateUsing(function (Activity $activity): string {
                        return '<code class="bg-gray-100 p-2 text-xs rounded">'.$activity->properties.'</code>';
                    })
                    ->html()
                    ->wrap()
                    ->label('Payload')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(static::sourceOptions())
                    ->label('Source')
                    ->query(function (Builder $query, array $data): Builder {
                        return static::applyApiScope($query, $data['value'] ?? null);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiActivities::route('/'),
        ];
    }

    protected static function applyApiScope(Builder $query, ?string $source = null): Builder
    {
        return $query
            ->where('log_name', '!=', 'notifications')
            ->whereIn('properties->source', static::sourcesForFilter($source));
    }

    /**
     * @return list<string>
     */
    private static function sourcesForFilter(?string $source): array
    {
        if (is_string($source) && array_key_exists($source, self::SOURCES)) {
            return [$source];
        }

        return array_keys(self::SOURCES);
    }

    /**
     * @return array<string, string>
     */
    private static function sourceOptions(): array
    {
        return self::SOURCES;
    }

    private static function sourceLabel(string $source): string
    {
        return self::SOURCES[$source] ?? ucfirst(str_replace('-', ' ', $source));
    }

    public static function sourceColor(string $source): string
    {
        return match ($source) {
            'cli', 'CLI' => 'warning',
            'desktop', 'Desktop' => 'info',
            'deploy-api', 'Deploy API' => 'danger',
            default => 'gray',
        };
    }

    public static function formatOccurredOn(mixed $state, ?string $targetTimezone = null): string
    {
        if ($state === null || $state === '') {
            return '—';
        }

        $timestamp = $state instanceof CarbonInterface
            ? $state
            : Carbon::parse((string) $state);

        return $timestamp
            ->timezone($targetTimezone ?? timezone())
            ->format(DT_FORMAT);
    }

    public static function applyApiScopeTo(Builder $query, ?string $source = null): Builder
    {
        return static::applyApiScope($query, $source);
    }
}
