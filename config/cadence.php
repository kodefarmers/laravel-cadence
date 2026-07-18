<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Strategy
    |--------------------------------------------------------------------------
    |
    | The backoff strategy used when no specific strategy is requested.
    |
    */

    'default' => env('CADENCE_DEFAULT_DRIVER', 'exponential'),

    /*
    |--------------------------------------------------------------------------
    | Backoff Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how Cadence manages backoff state.
    |
    */

    'free_attempts' => 3,

    'idle_timeout' => 3600,

    'cache' => [
        'store' => env('CADENCE_CACHE_STORE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Strategy Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the built-in backoff strategies.
    |
    */

    'drivers' => [

        'exponential' => [

            /*
             * The base value used to calculate exponential backoff delays.
             */
            'base_delay' => 2,

        ],

        'fibonacci' => [

            /*
             * The base value used to calculate fibonacci backoff delays.
             */
            'base_delay' => 1,

        ],

        'linear' => [

            /*
             * The base value used to calculate linear backoff delays.
             */
            'base_delay' => 2,

        ],

    ],

];
