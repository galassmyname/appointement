<?php

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        
        'api' => [
            'driver' => 'jwt',
            'provider' => 'users', 
        ],

        // Guard pour les prestataires
        'prestataire' => [
            'driver' => 'jwt',
            'provider' => 'prestataires',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // Provider pour les prestataires
        'prestataires' => [
            'driver' => 'eloquent',
            'model' => App\Models\Prestataire::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
