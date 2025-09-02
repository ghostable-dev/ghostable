<?php

namespace App\Billing\Enums;

enum BillingPolicy: string
{
    case RESPECT_SUBSCRIPTION = 'respect_subscription'; // normal Cashier flow
    case MANUAL_OVERRIDE = 'manual_override'; // bypass billing, force plan

    public function isManualOverride(): bool
    {
        return $this === self::MANUAL_OVERRIDE;
    }
}
