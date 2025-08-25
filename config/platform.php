<?php

return [

    /**
     * Billing configuration details.
     */
    'billing' => [
        'business' => [
            'type' => 'business',
            'api_id' => env('BUSINESS_SUBSCRIPTION_API_ID'),
        ],
        'enterprise' => [
            'type' => 'enterprise',
            'api_id' => env('ENT_SUBSCRIPTION_API_ID'),
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
