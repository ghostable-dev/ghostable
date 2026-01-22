<?php

declare(strict_types=1);

namespace App\Integration\Enums;

enum IntegrationDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';
}
