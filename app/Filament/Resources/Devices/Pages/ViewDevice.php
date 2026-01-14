<?php

namespace App\Filament\Resources\Devices\Pages;

use App\Crypto\Actions\LogDeviceActivity;
use App\Crypto\Actions\RevokeDevice as RevokeDeviceAction;
use App\Filament\Components\DateEntry;
use App\Filament\Resources\Devices\DeviceResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('revoke')
                ->label('Revoke Device')
                ->color('danger')
                ->icon('heroicon-m-no-symbol')
                ->visible(fn () => ! $this->record->isRevoked())
                ->requiresConfirmation()
                ->modalHeading('Revoke this device?')
                ->modalDescription('This device will be blocked from future crypto operations.')
                ->action(function (): void {
                    $user = auth()->user();
                    $revokedDevice = app(RevokeDeviceAction::class)->handle($this->record);

                    app(LogDeviceActivity::class)->handle(
                        device: $revokedDevice,
                        event: 'revoked',
                        user: $user,
                        context: [
                            'source' => 'filament',
                            'ip_address' => request()?->ip(),
                        ],
                    );
                })
                ->successNotificationTitle('Device revoked'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Device Details')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('platform'),
                    TextEntry::make('app_version')->label('App Version'),
                    IconEntry::make('active')
                        ->boolean()
                        ->label('Active'),
                    DateEntry::make('last_seen_at')->label('Last Seen'),
                    DateEntry::make('revoked_at')->label('Revoked At'),
                ])->columnSpanFull(),
                Section::make('Keys')->schema([
                    TextEntry::make('public_key')->label('Public Key')->copyable(),
                    TextEntry::make('public_signing_key')->label('Public Signing Key')->copyable(),
                ])->columnSpanFull(),
                Section::make('User')->schema([
                    TextEntry::make('user.name')->label('Name'),
                    TextEntry::make('user.email')->label('Email'),
                ])->columnSpanFull(),
                Section::make('Timestamps')->schema([
                    DateEntry::make('created_at')->label('Created At'),
                    DateEntry::make('updated_at')->label('Updated At'),
                ])->columnSpanFull(),
            ]);
    }
}
