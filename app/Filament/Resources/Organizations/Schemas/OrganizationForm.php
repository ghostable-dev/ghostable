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
                            ->relationship('owner', 'name'),
                        TextInput::make('slack_webhook_url'),
                        Toggle::make('slack_enabled')
                            ->required(),
                    ]),
                Section::make('Billing')
                    ->schema([
                        TextInput::make('stripe_id'),
                        Select::make('billing_policy')
                            ->options(BillingPolicy::class)
                            ->required(),
                        Select::make('plan_override')
                            ->options(Plan::class)
                            ->nullable()
                            ->visible(fn ($record) => $record->billing_policy->isManualOverride()),
                    ]),
            ]);
    }
}
