<?php

return [
    'poll_interval' => (int) env('CLI_LOGIN_POLL_INTERVAL', 3),

    'expires_in' => (int) env('CLI_LOGIN_EXPIRES_IN', 300),

    'token_cache_ttl' => (int) env('CLI_LOGIN_TOKEN_CACHE_TTL', 600),
];
