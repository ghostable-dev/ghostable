<?php

declare(strict_types=1);

namespace App\Integration\Integrations\Vanta\Enums;

enum MfaMethod: string
{
    case UNSUPPORTED = 'UNSUPPORTED';
    case DISABLED = 'DISABLED';
    case SMS = 'SMS';
    case EMAIL = 'EMAIL';
    case OTP = 'OTP';
    case HARDWARE_TOKEN = 'HARDWARE_TOKEN';
    case PUSH_PROMPT = 'PUSH_PROMPT';
}
