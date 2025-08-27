<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'api'),
        'passwords' => 'accounts',
    ],

    'guards' => [
        // Minimal web guard (session) to satisfy any 'auth:web' references
        'web' => [
            'driver' => 'session',
            'provider' => 'accounts',
        ],

        // JWT guard for APIs
        'api' => [
            'driver' => 'jwt',
            'provider' => 'accounts',
        ],
    ],

    'providers' => [
        'accounts' => [
            'driver' => 'eloquent',
            'model' => App\Models\Account::class,
        ],
    ],

    'passwords' => [
        'accounts' => [
            'provider' => 'accounts',
            // If your Laravel uses 'password_reset_tokens', change the table name accordingly:
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
