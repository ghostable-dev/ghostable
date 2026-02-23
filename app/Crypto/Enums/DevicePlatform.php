<?php

declare(strict_types=1);

namespace App\Crypto\Enums;

enum DevicePlatform: string
{
    case Unknown = 'unknown';
    case Web = 'web';
    case IOS = 'ios';
    case Android = 'android';
    case Linux = 'linux';
    case Windows = 'windows';
    case MacOS = 'macos';

    public static function fromStorageValue(?string $value): self
    {
        if ($value === null) {
            return self::Unknown;
        }

        $normalized = strtolower(trim($value));

        if ($normalized === '') {
            return self::Unknown;
        }

        $normalized = preg_replace('/\s*\([^)]*\)\s*$/', '', $normalized);
        $normalized = (string) $normalized;

        if (($platform = self::tryFrom((string) $normalized)) !== null) {
            return $platform;
        }

        $prefix = preg_replace('/[^a-z].*$/', '', (string) $normalized);

        return match ((string) $prefix) {
            'darwin', 'macos', 'macosx', 'osx', 'mac' => self::MacOS,
            'ios' => self::IOS,
            'android' => self::Android,
            'linux' => self::Linux,
            'windows' => self::Windows,
            'web' => self::Web,
            default => self::Unknown,
        };
    }
}
