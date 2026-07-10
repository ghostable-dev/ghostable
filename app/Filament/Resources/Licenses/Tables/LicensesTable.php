<?php

namespace App\Filament\Resources\Licenses\Tables;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Organization\Models\Organization;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label('Organization')
                    ->searchable()
                    ->sortable()
                    ->url(fn (License $record): string => OrganizationResource::getUrl('view', [
                        'record' => $record->organization->getKey(),
                    ])),
                TextColumn::make('purchaser_email')
                    ->label('Purchaser')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchaser.email')
                    ->label('Purchaser User')
                    ->searchable()
                    ->url(fn (License $record): ?string => $record->purchaser
                        ? UserResource::getUrl('view', ['record' => $record->purchaser->getKey()])
                        : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('plan')
                    ->formatStateUsing(fn (LicensePlan $state): string => $state->label())
                    ->badge()
                    ->color(fn (LicensePlan $state): string => match ($state) {
                        LicensePlan::Personal => 'gray',
                        LicensePlan::TeamFive, LicensePlan::TeamTen => 'info',
                        LicensePlan::Business => 'success',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (LicenseStatus $state): string => $state->label())
                    ->badge()
                    ->color(fn (LicenseStatus $state): string => self::statusColor($state))
                    ->sortable(),
                TextColumn::make('license_key_suffix')
                    ->label('Key')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? '**** '.$state : 'N/A')
                    ->searchable(),
                TextColumn::make('active_activations_count')
                    ->label('Active Devices')
                    ->counts('activeActivations')
                    ->sortable(),
                TextColumn::make('activations_count')
                    ->label('Devices')
                    ->counts('activations')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('activation_limit')
                    ->label('Device Limit')
                    ->sortable(),
                TextColumn::make('updates_until')
                    ->label('Updates Until')
                    ->formatStateUsing(fn ($state): string => $state?->timezone(timezone())->format(DT_FORMAT) ?? 'N/A')
                    ->sortable(),
                TextColumn::make('provider')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('provider_subscription_id')
                    ->label('Subscription')
                    ->limit(18)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->formatStateUsing(fn ($state): string => $state->timezone(timezone())->format(DT_FORMAT))
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('plan')
                    ->options(self::planOptions()),
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
                SelectFilter::make('provider')
                    ->options(fn (): array => License::query()
                        ->whereNotNull('provider')
                        ->orderBy('provider')
                        ->pluck('provider', 'provider')
                        ->all()),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->options(fn (): array => Organization::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                TernaryFilter::make('updates_current')
                    ->label('Updates Current')
                    ->nullable()
                    ->queries(
                        true: function (Builder $query): Builder {
                            $query->where('updates_until', '>', now());

                            return $query;
                        },
                        false: function (Builder $query): Builder {
                            $query->where(function (Builder $query): void {
                                $query->whereNull('updates_until')
                                    ->orWhere('updates_until', '<=', now());
                            });

                            return $query;
                        },
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('expired')
                    ->nullable()
                    ->queries(
                        true: function (Builder $query): Builder {
                            $query->where('expires_at', '<=', now());

                            return $query;
                        },
                        false: function (Builder $query): Builder {
                            $query->where(function (Builder $query): void {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            });

                            return $query;
                        },
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    /**
     * @return array<string, string>
     */
    private static function planOptions(): array
    {
        return collect(LicensePlan::cases())
            ->mapWithKeys(fn (LicensePlan $plan): array => [$plan->value => $plan->label()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function statusOptions(): array
    {
        return collect(LicenseStatus::cases())
            ->mapWithKeys(fn (LicenseStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    private static function statusColor(LicenseStatus $status): string
    {
        return match ($status) {
            LicenseStatus::Active => 'success',
            LicenseStatus::Inactive, LicenseStatus::Expired => 'warning',
            LicenseStatus::Revoked, LicenseStatus::Refunded => 'danger',
        };
    }
}
