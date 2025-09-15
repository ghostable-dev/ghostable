<?php

namespace App\Filament\Resources\Inquiries\Pages;

use App\Core\Enums\InquiryType;
use App\Filament\Components\DateEntry;
use App\Filament\Resources\Inquiries\InquiryResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class ViewInquiry extends ViewRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('From')->schema([
                    TextEntry::make('name'),
                    TextEntry::make('email'),
                ])->columnSpanFull(),
                Section::make('Message')->schema([
                    DateEntry::make('created_at')->label('Date'),
                    TextEntry::make('inquiry')->badge()->color(function ($record) {
                        return match ($record->inquiry) {
                            InquiryType::SUPPORT => Color::Red,
                            default => null,
                        };
                    }),
                    TextEntry::make('message'),
                ])->columnSpanFull(),
            ]);
    }
}
