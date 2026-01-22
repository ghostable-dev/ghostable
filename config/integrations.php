<?php

declare(strict_types=1);

return [
    'oauth' => [
        'code_ttl' => (int) env('INTEGRATION_OAUTH_CODE_TTL', 600),
        'access_token_ttl' => (int) env('INTEGRATION_OAUTH_ACCESS_TTL', 3600),
        'refresh_token_ttl' => (int) env('INTEGRATION_OAUTH_REFRESH_TTL', 1209600),
    ],
];
