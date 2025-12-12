<?php

return [
    'base_url' => env('VANTA_BASE_URL', 'https://api.vanta.com'),
    'client_id' => env('VANTA_CLIENT_ID'),
    'client_secret' => env('VANTA_CLIENT_SECRET'),
    'token_url' => env('VANTA_TOKEN_URL', 'https://api.vanta.com/oauth/token'),
    'default_scope' => env('VANTA_SCOPE', 'connectors.self:read-resource connectors.self:write-resource'),
    'resource_id' => env('VANTA_RESOURCE_ID'),
];
