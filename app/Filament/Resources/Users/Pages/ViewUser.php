<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Components\DateEntry;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
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
