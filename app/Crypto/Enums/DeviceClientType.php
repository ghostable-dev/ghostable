<?php

declare(strict_types=1);

namespace App\Crypto\Enums;

enum DeviceClientType: string
{
    case Cli = 'cli';
    case Desktop = 'desktop';
}
