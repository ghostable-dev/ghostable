<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Account\Models\User;
use App\Filament\Resources\Licenses\LicenseResource;
use App\Filament\Resources\Users\UserResource;
use App\Licensing\Actions\CreateManualLicenseGrant;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use App\Organization\Models\Organization;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LicensesRelationManager extends RelationManager
{
    protected static ?string $title = 'Licenses';

    protected static string $relationship = 'licenses';

    protected static ?string $recordTitleAttribute = 'purchaser_email';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
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
            ])
            ->headerActions([
                Action::make('generateLicense')
                    ->label('Generate and email license')
                    ->icon('heroicon-m-plus')
                    ->modalHeading(function (): string {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();

                        return 'Generate license for '.$organization->name;
                    })
                    ->modalSubmitActionLabel('Generate and email license')
                    ->form([
                        Select::make('plan')
                            ->options(self::planOptions())
                            ->default(LicensePlan::Personal->value)
                            ->required(),
                        TextInput::make('purchaser_email')
                            ->label('Recipient email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText('The license key will be emailed to this address.'),
                        Select::make('purchaser_user_id')
                            ->label('Attribute to org user')
                            ->options(fn (): array => $this->organizationUserOptions())
                            ->searchable()
                            ->nullable()
                            ->helperText('Optional attribution only. The license belongs to the organization either way.'),
                        Textarea::make('note')
                            ->label('Internal note')
                            ->rows(3)
                            ->maxLength(1000)
                            ->nullable()
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data): void {
                        /** @var Organization $organization */
                        $organization = $this->getOwnerRecord();
                        $purchaser = null;

                        if (filled($data['purchaser_user_id'] ?? null)) {
                            $purchaser = $organization->users()
                                ->whereKey($data['purchaser_user_id'])
                                ->first();

                            if (! $purchaser instanceof User) {
                                throw ValidationException::withMessages([
                                    'purchaser_user_id' => 'Select a user in this organization.',
                                ]);
                            }
                        }

                        $actor = Auth::user();

                        $result = app(CreateManualLicenseGrant::class)->execute(
                            organization: $organization,
                            plan: $data['plan'],
                            purchaserEmail: $data['purchaser_email'],
                            purchaser: $purchaser,
                            actor: $actor instanceof User ? $actor : null,
                            note: $data['note'] ?? null,
                        );

                        Notification::make()
                            ->title('License generated and emailed')
                            ->body('Sent to '.$result['license']->purchaser_email.'.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->url(fn (License $record): string => LicenseResource::getUrl('view', ['record' => $record->getKey()])),
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

    /**
     * @return array<string, string>
     */
    private function organizationUserOptions(): array
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        /** @var Collection<int, User> $users */
        $users = $organization->users()
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderBy('users.email')
            ->get();

        return $users
            ->mapWithKeys(fn (User $user): array => [
                (string) $user->getKey() => filled($user->name)
                    ? "{$user->name} <{$user->email}>"
                    : $user->email,
            ])
            ->all();
    }
}
