<?php

namespace App\Filament\Resources\Inquiries;

use App\Core\Enums\InquiryType;
use App\Core\Models\Inquiry;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Filament\Resources\Inquiries\Pages\ViewInquiry;
use App\Filament\Resources\Inquiries\Tables\InquiriesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return InquiriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'view' => ViewInquiry::route('/{record}'),
        ];
    }

    public static function inquiryBadge(mixed $input): mixed
    {
        return $input->badge()->color(function ($record) {
            return match ($record->inquiry) {
                InquiryType::SUPPORT => Color::Red,
                InquiryType::PARTNERSHIP => Color::Blue,
                InquiryType::SALES => Color::Green,
                default => null,
            };
        });
    }
}
