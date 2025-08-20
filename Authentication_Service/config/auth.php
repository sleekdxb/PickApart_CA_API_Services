<?php

return [

    'defaults' => [
        'guard' => 'api', // ✅ Change 'web' to 'api' if you're using JWT for all requests
        'passwords' => 'accounts', // ✅ Align with the correct provider
    ],

    'guards' => [
        'api' => [
            'driver' => 'jwt', // ✅ you're using JWT — good
            'provider' => 'accounts', // ✅ using the Account model
        ],
        'web' => [
            'driver' => 'session',
            'provider' => 'users', // optional fallback if you use web session logins
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'accounts' => [
            'driver' => 'eloquent',
            'model' => App\Models\Account::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
        'accounts' => [ // ✅ Add this for completeness
            'provider' => 'accounts',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
