<?php

declare(strict_types=1);

namespace App\Core\Enums;

enum DesktopUpdateEventType: string
{
    case AppcastChecked = 'appcast_checked';
    case DownloadRedirected = 'download_redirected';
    case UpdateDownloaded = 'update_downloaded';
    case UpdateInstalled = 'update_installed';
    case UpdateFailed = 'update_failed';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $eventType): array => [$eventType->value => $eventType->label()])
            ->all();
    }

    public static function telemetryOptions(): array
    {
        return collect([
            self::UpdateDownloaded,
            self::UpdateInstalled,
            self::UpdateFailed,
        ])->mapWithKeys(fn (self $eventType): array => [$eventType->value => $eventType->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::AppcastChecked => 'Appcast Checked',
            self::DownloadRedirected => 'Download Redirected',
            self::UpdateDownloaded => 'Update Downloaded',
            self::UpdateInstalled => 'Update Installed',
            self::UpdateFailed => 'Update Failed',
        };
    }
}
