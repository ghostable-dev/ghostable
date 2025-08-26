<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('User Details')
                ->schema([
                    Forms\Components\TextInput::make('id')->label('ID')->disabled(),
                    Forms\Components\TextInput::make('name')->disabled(),
                    Forms\Components\TextInput::make('email')->disabled(),
                    Forms\Components\TextInput::make('timezone')->disabled(),
                    Forms\Components\TextInput::make('email_verified_at')
                        ->label('Email Verified At')
                        ->disabled(),
                    Forms\Components\TextInput::make('stripe_id')
                        ->label('Stripe ID')
                        ->disabled(),
                    Forms\Components\TextInput::make('pm_type')
                        ->label('PM Type')
                        ->disabled(),
                    Forms\Components\TextInput::make('pm_last_four')
                        ->label('PM Last Four')
                        ->disabled(),
                    Forms\Components\TextInput::make('trial_ends_at')
                        ->label('Trial Ends At')
                        ->disabled(),
                    Forms\Components\TextInput::make('created_at')
                        ->label('Created At')
                        ->disabled(),
                    Forms\Components\TextInput::make('updated_at')
                        ->label('Updated At')
                        ->disabled(),
                ])
                ->columns(2),
        ];
    }
}
