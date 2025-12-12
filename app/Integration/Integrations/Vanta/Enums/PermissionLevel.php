<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Enums;

enum PermissionLevel: string
{
    case ADMIN = 'ADMIN';
    case EDITOR = 'EDITOR';
    case BASE = 'BASE';
}
