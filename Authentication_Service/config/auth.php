<?php

return [

    'defaults' => [
        'guard' => 'api', // ✅ Change 'web' to 'api' if you're using JWT for all requests
        'passwords' => 'accounts', // ✅ Align with the correct provider
    ],

    'guards' => [
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
