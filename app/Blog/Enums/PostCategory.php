<?php

namespace App\Blog\Enums;

enum PostCategory: string
{
    case PRODUCT_UPDATES = 'product-updates';
    case BEST_PRACTICES = 'best-practices';
    case SECURITY = 'security';
    case CASE_STUDIES = 'case-studies';
    case EVENTS = 'events';
    case RELEASE_NOTES = 'release-notes';

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
    
    public function description(): string
    {
        return match ($this) {
            self::PRODUCT_UPDATES => 'News about new Ghostable features, improvements, and roadmap highlights.',
            self::BEST_PRACTICES => 'Guides and advice for managing environment variables and Laravel apps securely.',
            self::SECURITY => 'Deep dives on security, compliance, and protecting sensitive configuration data.',
            self::CASE_STUDIES => 'Real-world stories of how teams use Ghostable to improve their workflows.',
            self::EVENTS => 'Announcements and recaps of Laracon, Wire:Live, and other community events.',
            self::RELEASE_NOTES => 'Detailed changelogs for Ghostable versions, fixes, and technical updates.',
        };
    }
}
