<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Billing\Enums\BillingPolicy;
use App\Billing\Enums\Plan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('slug'),
                        TextInput::make('name')
                            ->required(),
                        Select::make('owner_id')
                            ->relationship('owner', 'name')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                Section::make('Billing')
                    ->schema([
                        TextInput::make('stripe_id')
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('billing_policy')
                            ->options(BillingPolicy::class)
                            ->required(),
                        Select::make('plan_override')
                            ->options(Plan::class)
                            ->nullable()
                            ->visible(fn ($record) => $record?->billing_policy->isManualOverride()),
                        Toggle::make('is_partner')
                            ->label('Partner organization'),
                        Toggle::make('desktop_licensing_enabled')
                            ->label('Desktop licensing experience')
                            ->helperText('Checked organizations use the new license-based desktop flow. Unchecked organizations keep the legacy projects, integrations, and V2 API experience.'),
                    ]),
            ]);
    }
}
