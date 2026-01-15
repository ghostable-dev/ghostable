<?php

namespace App\Filament\Resources\Core\SecurityActivity;

use App\Account\Models\User;
use App\Core\Models\Activity;
use App\Filament\Columns\DateColumn;
use App\Filament\Resources\Core\SecurityActivity\Pages\ListSecurityActivities;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SecurityActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    protected static ?string $navigationLabel = 'Security';

    protected static ?string $label = 'Security Activity';

    public static ?string $slug = 'security-activity';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 2;

    private const CATEGORIES = [
        'authentication' => [
            'label' => 'Authentication',
            'criteria' => [
                [
                    'log' => 'user',
                    'events' => [
                        'login',
                        'login_failed',
                        'mfa_challenge',
                        'failed_mfa',
                        'mfa_succeeded',
                        '2fa-enabled',
                        '2fa-disabled',
                        'password_reset_requested',
                        'password_reset',
                    ],
                ],
            ],
        ],
        'privileged_actions' => [
            'label' => 'Privileged Actions',
            'criteria' => [
                [
                    'log' => 'user',
                    'events' => [
                        'admin_access',
                        'locked',
                        'unlocked',
                        'suspended',
                        'reinstated',
                        'role_changed',
                        'permission_override_granted',
                        'permission_override_revoked',
                    ],
                ],
                [
                    'log' => 'device',
                    'events' => [
                        'revoked',
                    ],
                ],
                [
                    'log' => 'cli-token',
                    'events' => [
                        'created',
                        'deleted',
                    ],
                ],
            ],
        ],
        'data_access' => [
            'label' => 'Data Access',
            'criteria' => [
                [
                    'log' => 'variable',
                    'events' => [
                        'downloaded',
                        'pulled',
                        'history_viewed',
                        'variable_history_viewed',
                        'project_history_viewed',
                        'deployment_token_created',
                        'deployment_token_rotated',
                        'deployment_token_revoked',
                    ],
                ],
            ],
        ],
        'system_changes' => [
            'label' => 'System Changes',
            'criteria' => [
                [
                    'log' => 'variable',
                    'events' => [
                        'deploy',
                        'push',
                        'rollback',
                    ],
                ],
                [
                    'log' => 'backup',
                    'events' => [
                        'created',
                    ],
                ],
            ],
        ],
        'incident_indicators' => [
            'label' => 'Incident Indicators',
            'criteria' => [
                [
                    'log' => 'user',
                    'events' => [
                        'login_failed',
                        'failed_mfa',
                        'mfa_succeeded',
                        'locked',
                        'suspended',
                        'permission_override_revoked',
                    ],
                ],
                [
                    'log' => 'device',
                    'events' => [
                        'revoked',
                    ],
                ],
            ],
        ],
    ];

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'DESC')
            ->modifyQueryUsing(function (Builder $query) {
                return static::applySecurityScope($query);
            })
            ->columns([
                DateColumn::make('created_at')
                    ->label('Occured On')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('event')
                    ->badge()
                    ->colors([
                        'success' => static fn ($state): bool => in_array($state, [
                            'login',
                            'mfa_succeeded',
                            '2fa-enabled',
                            'password_reset',
                        ]),
                        'warning' => static fn ($state): bool => in_array($state, [
                            'mfa_challenge',
                            'password_reset_requested',
                            'admin_access',
                            'role_changed',
                            'permission_override_granted',
                            'permission_override_revoked',
                        ]),
                        'danger' => static fn ($state): bool => in_array($state, [
                            'login_failed',
                            'failed_mfa',
                            'locked',
                            'suspended',
                        ]),
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
                SelectFilter::make('category')
                    ->options(static::categoryOptions())
                    ->label('Category')
                    ->query(function (Builder $query, array $data) {
                        return static::applySecurityScope($query, $data['value'] ?? null);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSecurityActivities::route('/'),
        ];
    }

    protected static function applySecurityScope(Builder $query, ?string $category = null): Builder
    {
        $categories = static::categoriesForFilter($category);

        return $query->where(function (Builder $query) use ($categories) {
            foreach ($categories as $categoryDefinition) {
                foreach ($categoryDefinition['criteria'] as $criteria) {
                    $query->orWhere(function (Builder $query) use ($criteria) {
                        if (array_key_exists('log', $criteria)) {
                            $query->where('log_name', $criteria['log']);
                        }

                        if (array_key_exists('events', $criteria)) {
                            $query->whereIn('event', $criteria['events']);
                        }
                    });
                }
            }
        });
    }

    private static function categoriesForFilter(?string $category): array
    {
        if ($category && array_key_exists($category, static::CATEGORIES)) {
            return [static::CATEGORIES[$category]];
        }

        return array_values(static::CATEGORIES);
    }

    private static function categoryOptions(): array
    {
        $options = [];

        foreach (static::CATEGORIES as $key => $definition) {
            $options[$key] = $definition['label'];
        }

        return $options;
    }

    public static function applySecurityScopeTo(Builder $query): Builder
    {
        return static::applySecurityScope($query);
    }
}
