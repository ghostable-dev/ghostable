<?php

namespace App\Core\Enums;

enum InquiryType: string
{
    case SALES = 'sales';
    case SUPPORT = 'support';
    case PARTNERSHIP = 'partnership';
    case SECURITY = 'security';
    case OTHER = 'other';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::SALES => 'Sales',
            self::SUPPORT => 'Support',
            self::PARTNERSHIP => 'Partnership',
            self::SECURITY => 'Security',
            self::OTHER => 'Other',
        };
    }
}
