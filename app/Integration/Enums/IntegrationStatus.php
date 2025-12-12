<?php

declare(strict_types=1);

namespace App\Integration\Enums;

enum IntegrationStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Failed = 'failed';
}
