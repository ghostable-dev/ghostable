<?php

declare(strict_types=1);

namespace App\Organization\Enums;

enum OrganizationAuditWebhookStatus: string
{
    case ACTIVE = 'active';
    case DISABLED = 'disabled';
    case DEAD_LETTER = 'dead_letter';
}
