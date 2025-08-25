<?php

return [

    /**
     * Billing configuration details.
     */
    'billing' => [
        'starter' => [
            'type' => 'starter',
            'api_id' => env('STARTER_SUBSCRIPTION_API_ID'),
        ],
        'growth' => [
            'type' => 'growth',
            'api_id' => env('GROWTH_SUBSCRIPTION_API_ID'),
        ],
        'enterprise' => [
            'type' => 'enterprise',
            'api_id' => env('ENTERPRISE_SUBSCRIPTION_API_ID'),
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
