<?php

return [
    'auth' => [
        'access_token_ttl'      => (int) env(
            'ADMIN_ACCESS_TOKEN_TTL',
            60
        ),
        'refresh_token_ttl'     => (int) env(
            'ADMIN_REFRESH_TOKEN_TTL',
            43200
        ),
        'access_token_ability'  => 'admin:access',
        'refresh_token_ability' => 'admin:refresh',
    ],
];
