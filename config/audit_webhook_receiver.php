<?php

declare(strict_types=1);

return [
    'driver' => env('AUDIT_WEBHOOK_RECEIVER_DRIVER', 'null'),
    'log_channel' => env('AUDIT_WEBHOOK_RECEIVER_LOG_CHANNEL', 'audit_webhook_receiver'),
    'token' => env('AUDIT_WEBHOOK_RECEIVER_TOKEN'),
    'local_routes_enabled' => filter_var(
        (string) env(
            'AUDIT_WEBHOOK_LOCAL_ROUTES_ENABLED',
            strtolower((string) env('APP_ENV', 'production')) === 'local' ? 'true' : 'false'
        ),
        FILTER_VALIDATE_BOOLEAN
    ),
    'retention_days' => max(1, (int) env('AUDIT_WEBHOOK_RECEIVER_RETENTION_DAYS', 14)),
];
