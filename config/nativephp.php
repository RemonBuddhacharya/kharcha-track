<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    */
    'version' => env('NATIVEPHP_APP_VERSION', '1.0.0'),
    'version_code' => env('NATIVEPHP_APP_VERSION_CODE', 1),

    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    */
    'app_id' => env('NATIVEPHP_APP_ID', 'com.chandan.kharcha-track'),

    /*
    |--------------------------------------------------------------------------
    | Deep Linking
    |--------------------------------------------------------------------------
    */
    'deeplink_scheme' => env('NATIVEPHP_DEEPLINK_SCHEME'),
    'deeplink_host' => env('NATIVEPHP_DEEPLINK_HOST'),

    /*
    |--------------------------------------------------------------------------
    | Start URL
    |--------------------------------------------------------------------------
    */
    'start_url' => env('NATIVEPHP_START_URL', '/'),

    /*
    |--------------------------------------------------------------------------
    | iOS Development Team ID
    |--------------------------------------------------------------------------
    */
    'development_team' => env('NATIVEPHP_DEVELOPMENT_TEAM'),

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Configure device permissions with custom usage descriptions for iOS.
    |
    */
    'permissions' => [
        'push_notifications' => true,
        'biometrics' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | iPad Support
    |--------------------------------------------------------------------------
    |
    | When enabled, all orientations must be allowed.
    |
    */
    'ipad' => false,

    /*
    |--------------------------------------------------------------------------
    | Device Orientation
    |--------------------------------------------------------------------------
    */
    'orientation' => [
        'iphone' => [
            'portrait' => true,
            'upside_down' => false,
            'landscape_left' => false,
            'landscape_right' => false,
        ],
        'android' => [
            'portrait' => true,
            'upside_down' => false,
            'landscape_left' => false,
            'landscape_right' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Service Port
    |--------------------------------------------------------------------------
    |
    | The preferred port for the iOS debug service (hot reload, log retrieval).
    | If unavailable, the service scans ports 9000-9999.
    |
    */
    'debug_port' => env('NATIVEPHP_DEBUG_PORT', 9000),

    /*
    |--------------------------------------------------------------------------
    | Hot Reload Configuration
    |--------------------------------------------------------------------------
    */
    'hot_reload' => [
        'watch_paths' => [
            'app',
            'resources',
            'routes',
            'config',
            'public',
        ],
        'exclude_patterns' => [
            '\.git',
            'storage',
            'tests',
            'nativephp',
            'credentials',
            'node_modules',
            '\.swp',
            '\.tmp',
            '~',
            '\.log',
        ],
    ],
];
