<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Enums;

enum ResourceStatus: string
{
    case ACTIVE = 'ACTIVE';
    case DEACTIVATED = 'DEACTIVATED';
}
