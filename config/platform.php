<?php

use App\Billing\Enums\Plan;

return [

    /**
     * Billing configuration details.
     */
    'billing' => [
        Plan::STANDARD->value => [
            'type' => Plan::STANDARD->value,
            'api_id' => env('STANDARD_SUBSCRIPTION_API_ID'),
        ],
        Plan::SCALE->value => [
            'type' => Plan::SCALE->value,
            'api_id' => env('SCALE_SUBSCRIPTION_API_ID'),
        ],
    ],

    /**
     * Organization invites.
     */
    'invite' => [
        'resend_cooldown_minutes' => env('INVITE_RESEND_COOLDOWN_MINUTES', 5),
        'expiration_days' => env('INVITE_EXPIRATION_DAYS', 7),
    ],
];
