<?php

namespace App\Filament\Resources\MailingListEmails\Pages;

use App\Account\Enums\MailingListEmailSource;
use App\Filament\Components\DateEntry;
use App\Filament\Resources\MailingListEmails\MailingListEmailResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMailingListEmail extends ViewRecord
{
    protected static string $resource = MailingListEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->schema([
                        TextEntry::make('email'),
                        TextEntry::make('source')
                            ->label('Source')
                            ->formatStateUsing(function ($state) {
                                if ($state instanceof MailingListEmailSource) {
                                    return $state->label();
                                }

                                if (is_string($state)) {
                                    $enum = MailingListEmailSource::tryFrom($state);

                                    return $enum?->label() ?? ucwords(str_replace('_', ' ', $state));
                                }

                                return $state;
                            }),
                        DateEntry::make('created_at')
                            ->label('Signed Up At'),
                        DateEntry::make('deleted_at')
                            ->label('Unsubscribed At'),
                    ])
                    ->columnSpanFull(),
                Section::make('Notification Preferences')
                    ->columns(2)
                    ->schema([
                        IconEntry::make('blog_notifications')
                            ->label('Blog Updates')
                            ->boolean()
                            ->getStateUsing(fn ($record) => (bool) ($record->notifications->blog ?? false)),
                        IconEntry::make('promotional_notifications')
                            ->label('Promotions')
                            ->boolean()
                            ->getStateUsing(fn ($record) => (bool) ($record->notifications->promotional ?? false)),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
