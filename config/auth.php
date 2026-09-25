<?php

use App\Models\Admin;
use App\Models\ClientUser;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'client'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'client_users'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Nusa Hotspot has two separate login areas: "admin" for the superadmin
    | panel (table: admins) and "client" for the tenant/client panel
    | (table: client_users). There is no default Laravel "users" table.
    |
    */

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'client' => [
            'driver' => 'session',
            'provider' => 'client_users',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => Admin::class,
        ],
        'client_users' => [
            'driver' => 'eloquent',
            'model' => ClientUser::class,
        ],
    ],

    'passwords' => [
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'client_users' => [
            'provider' => 'client_users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
