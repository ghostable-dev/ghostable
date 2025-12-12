<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Enums;

enum AuthMethod: string
{
    case SSO = 'SSO';
    case PASSWORD = 'PASSWORD';
    case TOKEN = 'TOKEN';
    case BIOMETRIC = 'BIOMETRIC';
}
