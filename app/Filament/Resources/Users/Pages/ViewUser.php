<?php

namespace App\Filament\Resources\Users\Pages;

use App\Account\Actions\UserStatus\LockUser;
use App\Account\Actions\UserStatus\ReinstateUser;
use App\Account\Actions\UserStatus\SuspendUser;
use App\Account\Actions\UserStatus\UnlockUser;
use App\Account\Enums\UserStatus;
use App\Filament\Components\DateEntry;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('suspend')
                ->label('Suspend')
                ->color('danger')
                ->icon('heroicon-m-no-symbol')
                ->visible(fn () => $this->record->isActive() && ! $this->record->isFounder())
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
                ->action(function (array $data): void {
                    $reason = $data['reason_code'] ?? null;

                    if ($reason === 'other_custom') {
                        $reason = $data['reason_custom'] ?? null;
                    }

                    app(SuspendUser::class)->handle($this->record, auth()->user(), $reason);
                })
                ->successNotificationTitle('User suspended'),
            Action::make('reinstate')
                ->label('Reinstate')
                ->color('success')
                ->icon('heroicon-m-play')
                ->visible(fn () => $this->record->isSuspended() && ! $this->record->isFounder())
                ->requiresConfirmation()
                ->action(fn () => app(ReinstateUser::class)->handle($this->record, auth()->user()))
                ->successNotificationTitle('User reinstated'),
            Action::make('lock')
                ->label('Lock')
                ->color('warning')
                ->icon('heroicon-m-lock-closed')
                ->visible(fn () => ! $this->record->isLocked() && ! $this->record->isFounder())
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
                ->action(function (array $data): void {
                    $reason = $data['reason_code'] ?? null;

                    if ($reason === 'other_custom') {
                        $reason = $data['reason_custom'] ?? null;
                    }

                    app(LockUser::class)->handle($this->record, auth()->user(), $reason);
                })
                ->successNotificationTitle('User locked'),
            Action::make('unlock')
                ->label('Unlock')
                ->color('success')
                ->icon('heroicon-m-lock-open')
                ->visible(fn () => $this->record->isLocked() && ! $this->record->isFounder())
                ->requiresConfirmation()
                ->action(fn () => app(UnlockUser::class)->handle($this->record, auth()->user()))
                ->successNotificationTitle('User unlocked'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email')->label('Email address'),
                        TextEntry::make('status')
                            ->formatStateUsing(fn (UserStatus $state) => $state->label())
                            ->color(fn (UserStatus $state): string => match ($state) {
                                UserStatus::ACTIVE => 'success',
                                UserStatus::SUSPENDED => 'danger',
                                UserStatus::LOCKED => 'warning',
                            })
                            ->badge(),
                        IconEntry::make('email_verified_at')
                            ->boolean(fn ($state) => ! is_null($state))
                            ->default(false)
                            ->label('Verified'),
                        DateEntry::make('email_verified_at'),
                    ])->columnSpanFull(),
                Section::make('Location')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('timezone'),
                        TextEntry::make('local_time')
                            ->label('Local Time')
                            ->getStateUsing(function ($record) {
                                $tz = $record->timezone ?? config('app.timezone');

                                return Carbon::now($tz)->format('m/d/Y g:i A');
                            })->placeholder('N/A'),
                    ])->columnSpanFull(),
                Section::make('Security')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('two_factor_confirmed_at')
                            ->boolean(fn ($state) => ! is_null($state))
                            ->default(false)
                            ->label('2FA Enabled'),
                        DateEntry::make('two_factor_confirmed_at'),
                    ])->columnSpanFull(),
                // Tabs::make('Tabs')
                //     ->columnSpanFull()
                //     ->tabs([
                //         //self::generalTab(),
                //         //self::typeTab(),
                //         self::organizationsTab(),
                //         //self::billingTab()
                //     ]),
            ]);
    }

    protected static function organizationsTab(): Tab
    {
        return Tab::make('Organizations')
            ->schema([
                TextEntry::make('organizations.name')
                    ->label('Name'),
                // TextEntry::make('organization.description')
                //     ->label('Description'),
                // TextEntry::make('organization.url')
                //     ->label('URL'),
                // ImageEntry::make('organization_logo')
                //     ->label('Logo')
                //     ->size('sm')
                //     ->getStateUsing(fn($record) => $record->organization->logoUrl()),
                // ColorEntry::make('organization.brand.hex')
                //     ->label('Brand Color')
                //     ->copyable(),
                // ColorEntry::make('organization.accent.hex')
                //     ->label('Accent Color')
                //     ->copyable(),
            ])->columns(2);
    }
}
