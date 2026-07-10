<?php

declare(strict_types=1);

$signingKeys = json_decode((string) env('GHOSTABLE_LICENSE_SIGNING_KEYS', '[]'), true);

return [
    'checkout' => [
        'success_url' => env('GHOSTABLE_LICENSE_CHECKOUT_SUCCESS_URL'),
        'cancel_url' => env('GHOSTABLE_LICENSE_CHECKOUT_CANCEL_URL'),
        'stripe_prices' => [
            'personal' => env('GHOSTABLE_STRIPE_PRICE_PERSONAL'),
            'team_5' => env('GHOSTABLE_STRIPE_PRICE_TEAM_5'),
            'team_10' => env('GHOSTABLE_STRIPE_PRICE_TEAM_10'),
        ],
    ],

    'entitlements' => [
        'ttl_minutes' => (int) env('GHOSTABLE_LICENSE_ENTITLEMENT_TTL_MINUTES', 10080),
    ],

    'updates' => [
        'latest_version' => env('GHOSTABLE_DESKTOP_LATEST_VERSION', '0.1.0'),
    ],

    'recovery' => [
        'window_days' => (int) env('GHOSTABLE_LICENSE_RECOVERY_WINDOW_DAYS', 30),
        'single_device_deactivations_per_window' => (int) env('GHOSTABLE_LICENSE_SINGLE_DEVICE_DEACTIVATIONS_PER_WINDOW', 5),
        'all_device_deactivations_per_window' => (int) env('GHOSTABLE_LICENSE_ALL_DEVICE_DEACTIVATIONS_PER_WINDOW', 2),
        'secure_license_resets_per_window' => (int) env('GHOSTABLE_LICENSE_SECURE_RESETS_PER_WINDOW', 2),
    ],

    'signing' => [
        'active_key_id' => env('GHOSTABLE_LICENSE_SIGNING_KEY_ID'),
        'keys' => is_array($signingKeys) ? $signingKeys : [],
    ],
];
