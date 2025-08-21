<?php

namespace App\Blog\Enums;

enum PostCategory: string
{
    case PRODUCT_UPDATES = 'product_updates';
    case BEST_PRACTICES = 'best_practices';
    case SECURITY = 'security';
    case CASE_STUDIES = 'case_studies';
    case EVENTS = 'events';
    case RELEASE_NOTES = 'release_notes';

    public static function selectOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
            ->toArray();
    }

    public function label(): string
    {
        return match ($this) {
            self::PRODUCT_UPDATES => 'Product Updates',
            self::BEST_PRACTICES => 'Best Practices',
            self::SECURITY => 'Security',
            self::CASE_STUDIES => 'Case Studies',
            self::EVENTS => 'Events',
            self::RELEASE_NOTES => 'Release Notes',
        };
    }
}
