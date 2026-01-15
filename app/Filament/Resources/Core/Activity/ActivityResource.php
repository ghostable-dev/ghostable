<?php

namespace App\Filament\Resources\Core\Activity;

use App\Account\Models\User;
use App\Filament\Columns\DateColumn;
use App\Filament\Resources\Core\Activity\Pages\ListActivities;
use App\Filament\Resources\Users\UserResource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Activity';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'All';

    protected static ?string $label = 'All Activity';

    public static ?string $slug = 'activities';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'DESC')
            ->modifyQueryUsing(function ($query) {
                return $query->where('log_name', '!=', 'notifications');
            })
            ->columns([
                DateColumn::make('created_at')
                    ->label('Occured On')
                    ->searchable()
                    ->sortable(),
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
                    ->limit(20)
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
                            // Account::class => AccountResource::getUrl('view', ['record' => $activity->subject->id]),
                            User::class => UserResource::getUrl('view', ['record' => $activity->subject->id]),
                            // Job::class => JobResource::getUrl('view', ['record' => $activity->subject->id]),
                            default => null,
                        };
                    })
                    ->limit(20)
                    ->tooltip(fn (Activity $activity) => $activity->subject?->id),
                TextColumn::make('event')
                    ->colors([
                        'secondary',
                        'danger' => static fn ($state): bool => in_array($state, [
                            'deleted',
                            'disconnected',
                            'disabled',
                            'job-reported',
                            'login_failed',
                            'revoked',
                            'suspended',
                        ]),
                        'warning' => static fn ($state): bool => in_array($state, [
                            'updated',
                            'logout',
                            'job-report-ignored',
                            'configuration-updated',
                            '2fa-disabled',
                            'password_reset_requested',
                            'locked',
                        ]),
                        'success' => static fn ($state): bool => in_array($state, [
                            'registered',
                            'created',
                            'login',
                            '2fa-enabled',
                            'password_reset',
                            'enabled',
                            'connected',
                            'applied-to-job',
                            'reinstated',
                            'unlocked',
                        ]),
                    ])
                    ->badge()
                    ->searchable(),
                TextColumn::make('properties')
                    ->getStateUsing(function (Activity $activity): string {
                        return '<code class="bg-gray-100 p-2 text-xs rounded">'.$activity->properties.'</code>';
                    })->html()
                    ->wrap()
                    ->searchable()
                    ->label('Payload'),
            ])
            ->filters([
                SelectFilter::make('subject_type')
                    ->options([
                        'job' => 'Jobs',
                        'user' => 'Users',
                        'account' => 'Accounts',
                        'integration' => 'Integrations',
                    ])
                    ->label('Subject')
                    ->query(function (Builder $query, array $data) {
                        return $query->when(! is_null($data['value']), function (Builder $query) use ($data) {
                            $query->where('subject_type', $data['value']);
                        }, null);
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
