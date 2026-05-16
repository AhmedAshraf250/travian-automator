<?php

return [
    'http' => [
        'verify' => env('TRAVIAN_HTTP_VERIFY', true),
        'ca_bundle' => env('TRAVIAN_HTTP_CA_BUNDLE'),
        'timeout' => (int) env('TRAVIAN_HTTP_TIMEOUT', 20),
        'connect_timeout' => (int) env('TRAVIAN_HTTP_CONNECT_TIMEOUT', 10),
    ],
    'paths' => [
        'landing' => env('TRAVIAN_PATH_LANDING', '/dorf1.php'),
        'overview' => env('TRAVIAN_PATH_OVERVIEW', '/dorf1.php'),
        'village_center' => env('TRAVIAN_PATH_VILLAGE_CENTER', '/dorf2.php'),
        'build' => env('TRAVIAN_PATH_BUILD', '/build.php'),
        'auth_login' => env('TRAVIAN_PATH_AUTH_LOGIN', '/api/v1/auth/login'),
        'auth_redirect' => env('TRAVIAN_PATH_AUTH_REDIRECT', '/api/v1/auth'),
    ],
    'client' => [
        'accept_language' => env('TRAVIAN_CLIENT_ACCEPT_LANGUAGE', 'en-US,en;q=0.9'),
        'window_size' => env('TRAVIAN_CLIENT_WINDOW_SIZE', '1920:1200'),
        'mobile_optimizations' => env('TRAVIAN_CLIENT_MOBILE_OPTIMIZATIONS', false),
    ],
    'transport' => [
        'force_relogin_on_change' => env('TRAVIAN_FORCE_RELOGIN_ON_TRANSPORT_CHANGE', true),
    ],
    'server' => [
        'timezone' => env('TRAVIAN_SERVER_TIMEZONE', 'Europe/London'),
    ],
    'debug' => [
        'dump_dorf1_response' => env('TRAVIAN_DEBUG_DUMP_DORF1_RESPONSE', false),
    ],
];
