<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('stripe_id'),
                TextInput::make('slug'),
                TextInput::make('name')
                    ->required(),
                Select::make('owner_id')
                    ->relationship('owner', 'name'),
                TextInput::make('slack_webhook_url'),
                Toggle::make('slack_enabled')
                    ->required(),
            ]);
    }
}
