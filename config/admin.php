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

    'registration' => [
        // Optional. When set, first-admin registration must send this value
        // as `setup_key`. Strongly recommended for any internet-facing deploy.
        'setup_key' => env('ADMIN_SETUP_KEY'),

        // Role granted to the first administrator.
        'role_code' => 'SUPER_ADMIN',
    ],

    'password_reset' => [
        // Minutes a reset link stays valid.
        'ttl'          => (int) env('ADMIN_PASSWORD_RESET_TTL', 60),

        // Seconds before another reset e-mail is sent to the same admin.
        'cooldown'     => (int) env('ADMIN_PASSWORD_RESET_COOLDOWN', 60),

        // Next.js admin app; the link is {frontend_url}{path}?token=…&email=…
        'frontend_url' => env('ADMIN_FRONTEND_URL', env('APP_URL')),
        'path'         => '/reset-password',
    ],
];
