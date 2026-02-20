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
}
