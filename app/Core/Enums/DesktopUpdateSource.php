<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum DesktopUpdateSource: string
{
    case LatestDownload = 'latest_download';
    case Sparkle = 'sparkle';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $source): array => [$source->value => $source->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::LatestDownload => 'Latest Download',
            self::Sparkle => 'Sparkle',
        };
    }
}
