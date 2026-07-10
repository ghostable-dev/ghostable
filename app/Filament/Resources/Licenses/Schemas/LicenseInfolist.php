<?php

namespace App\Filament\Resources\Licenses\Schemas;

use App\Filament\Components\DateEntry;
use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Resources\Users\UserResource;
use App\Licensing\Enums\LicensePlan;
use App\Licensing\Enums\LicenseStatus;
use App\Licensing\Models\License;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class LicenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('License')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('organization.name')
                            ->label('Organization')
                            ->url(fn (License $record): string => OrganizationResource::getUrl('view', [
                                'record' => $record->organization->getKey(),
                            ])),
                        TextEntry::make('purchaser_email')
                            ->label('Purchaser')
                            ->copyable(),
                        TextEntry::make('purchaser.email')
                            ->label('Purchaser User')
                            ->placeholder('N/A')
                            ->url(fn (License $record): ?string => $record->purchaser
                                ? UserResource::getUrl('view', ['record' => $record->purchaser->getKey()])
                                : null),
                        TextEntry::make('masked_license_key')
                            ->label('License Key')
                            ->state(fn (License $record): string => $record->maskedLicenseKey())
                            ->copyable(),
                        TextEntry::make('status')
                            ->formatStateUsing(fn (LicenseStatus $state): string => $state->label())
                            ->badge()
                            ->color(fn (LicenseStatus $state): string => self::statusColor($state)),
                        TextEntry::make('plan')
                            ->formatStateUsing(fn (LicensePlan $state): string => $state->label())
                            ->badge()
                            ->color(fn (LicensePlan $state): string => match ($state) {
                                LicensePlan::Personal => 'gray',
                                LicensePlan::TeamFive, LicensePlan::TeamTen => 'info',
                                LicensePlan::Business => 'success',
                            }),
                        TextEntry::make('seat_count')
                            ->label('Seats'),
                        TextEntry::make('activation_limit')
                            ->label('Device Limit'),
                        TextEntry::make('active_activations_count')
                            ->label('Active Devices')
                            ->counts('activeActivations'),
                        TextEntry::make('activations_count')
                            ->label('Total Devices')
                            ->counts('activations'),
                        TextEntry::make('events_count')
                            ->label('Events')
                            ->counts('events'),
                        TextEntry::make('features')
                            ->label('Features')
                            ->state(fn (License $record): array => collect($record->features())
                                ->map(fn (string $feature): string => Str::of($feature)->replace('_', ' ')->title()->toString())
                                ->all())
                            ->listWithLineBreaks()
                            ->badge()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
                Section::make('Provider')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('provider')
                            ->placeholder('N/A')
                            ->badge(),
                        TextEntry::make('provider_customer_id')
                            ->label('Customer ID')
                            ->placeholder('N/A')
                            ->copyable(),
                        TextEntry::make('provider_checkout_id')
                            ->label('Checkout ID')
                            ->placeholder('N/A')
                            ->copyable(),
                        TextEntry::make('provider_subscription_id')
                            ->label('Subscription ID')
                            ->placeholder('N/A')
                            ->copyable(),
                    ])->columnSpanFull(),
                Section::make('Lifecycle')
                    ->columns(2)
                    ->schema([
                        DateEntry::make('updates_until')
                            ->label('Updates Until'),
                        DateEntry::make('expires_at')
                            ->label('Expires At'),
                        DateEntry::make('created_at')
                            ->label('Created At'),
                        DateEntry::make('updated_at')
                            ->label('Updated At'),
                    ])->columnSpanFull(),
                Section::make('Provider Metadata')
                    ->schema([
                        TextEntry::make('provider_metadata')
                            ->label('Metadata')
                            ->state(fn (License $record): string => self::formatJson($record->provider_metadata))
                            ->html()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    private static function statusColor(LicenseStatus $status): string
    {
        return match ($status) {
            LicenseStatus::Active => 'success',
            LicenseStatus::Inactive, LicenseStatus::Expired => 'warning',
            LicenseStatus::Revoked, LicenseStatus::Refunded => 'danger',
        };
    }

    private static function formatJson(?array $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        return '<code class="bg-gray-100 p-2 text-xs rounded">'.e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)).'</code>';
    }
}
