<?php

namespace App\Filament\Resources\Users\Tables;

use App\Account\Actions\UserStatus\LockUser;
use App\Account\Actions\UserStatus\ReinstateUser;
use App\Account\Actions\UserStatus\SuspendUser;
use App\Account\Actions\UserStatus\UnlockUser;
use App\Account\Enums\UserStatus;
use App\Account\Models\User;
use App\Filament\Columns\DateColumn;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('status')
                    ->formatStateUsing(fn (UserStatus $state) => $state->label())
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::ACTIVE => 'success',
                        UserStatus::SUSPENDED => 'danger',
                        UserStatus::LOCKED => 'warning',
                    })
                    ->badge(),
                IconColumn::make('email_verified_at')
                    ->boolean()
                    ->label('Verified'),
                DateColumn::make('last_login')
                    ->label('Last login')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateColumn::make('created_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                DateColumn::make('updated_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                DateColumn::make('deleted_at')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('suspend')
                        ->label('Suspend')
                        ->color('danger')
                        ->icon('heroicon-m-no-symbol')
                        ->visible(fn (User $record): bool => $record->isActive() && ! $record->isFounder())
                        ->form([
                            Select::make('reason_code')
                                ->label('Reason')
                                ->options([
                                    'policy_violation' => 'Policy violation',
                                    'suspected_abuse' => 'Suspected abuse',
                                    'account_compromise' => 'Account compromise',
                                    'billing_issue' => 'Billing issue',
                                    'user_request' => 'User request',
                                    'legal_request' => 'Legal request',
                                    'inactivity' => 'Inactivity',
                                    'duplicate_account' => 'Duplicate account',
                                    'other_custom' => 'Other (custom)',
                                ])
                                ->required(),
                            Textarea::make('reason_custom')
                                ->label('Custom reason')
                                ->rows(3)
                                ->maxLength(500)
                                ->visible(fn (callable $get) => $get('reason_code') === 'other_custom')
                                ->required(fn (callable $get) => $get('reason_code') === 'other_custom'),
                        ])
                        ->requiresConfirmation()
                        ->action(function (User $record, array $data): void {
                            $reason = $data['reason_code'] ?? null;

                            if ($reason === 'other_custom') {
                                $reason = $data['reason_custom'] ?? null;
                            }

                            app(SuspendUser::class)->handle($record, auth()->user(), $reason);
                        })
                        ->successNotificationTitle('User suspended'),
                    Action::make('reinstate')
                        ->label('Reinstate')
                        ->color('success')
                        ->icon('heroicon-m-play')
                        ->visible(fn (User $record): bool => $record->isSuspended() && ! $record->isFounder())
                        ->requiresConfirmation()
                        ->action(fn (User $record) => app(ReinstateUser::class)->handle($record, auth()->user()))
                        ->successNotificationTitle('User reinstated'),
                    Action::make('lock')
                        ->label('Lock')
                        ->color('warning')
                        ->icon('heroicon-m-lock-closed')
                        ->visible(fn (User $record): bool => ! $record->isLocked() && ! $record->isFounder())
                        ->form([
                            Select::make('reason_code')
                                ->label('Reason')
                                ->options([
                                    'suspicious_login' => 'Suspicious login',
                                    'failed_mfa' => 'Failed MFA',
                                    'brute_force_attempt' => 'Brute force attempt',
                                    'anomalous_device' => 'Anomalous device',
                                    'anomalous_ip' => 'Anomalous IP',
                                    'session_risk_detected' => 'Session risk detected',
                                    'other_custom' => 'Other (custom)',
                                ])
                                ->required(),
                            Textarea::make('reason_custom')
                                ->label('Custom reason')
                                ->rows(3)
                                ->maxLength(500)
                                ->visible(fn (callable $get) => $get('reason_code') === 'other_custom')
                                ->required(fn (callable $get) => $get('reason_code') === 'other_custom'),
                        ])
                        ->requiresConfirmation()
                        ->action(function (User $record, array $data): void {
                            $reason = $data['reason_code'] ?? null;

                            if ($reason === 'other_custom') {
                                $reason = $data['reason_custom'] ?? null;
                            }

                            app(LockUser::class)->handle($record, auth()->user(), $reason);
                        })
                        ->successNotificationTitle('User locked'),
                    Action::make('unlock')
                        ->label('Unlock')
                        ->color('success')
                        ->icon('heroicon-m-lock-open')
                        ->visible(fn (User $record): bool => $record->isLocked() && ! $record->isFounder())
                        ->requiresConfirmation()
                        ->action(fn (User $record) => app(UnlockUser::class)->handle($record, auth()->user()))
                        ->successNotificationTitle('User unlocked'),
                    ViewAction::make(),
                ])->label('Actions'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
