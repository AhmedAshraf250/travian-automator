<?php

return [
    'http' => [
        'verify' => env('TRAVIAN_HTTP_VERIFY', true),
        'ca_bundle' => env('TRAVIAN_HTTP_CA_BUNDLE'),
        'timeout' => (int) env('TRAVIAN_HTTP_TIMEOUT', 45),
        'connect_timeout' => (int) env('TRAVIAN_HTTP_CONNECT_TIMEOUT', 20),
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
        'accept_encoding' => env('TRAVIAN_CLIENT_ACCEPT_ENCODING', 'gzip, deflate'),
        'window_size' => env('TRAVIAN_CLIENT_WINDOW_SIZE', '1920:1200'),
        'mobile_optimizations' => env('TRAVIAN_CLIENT_MOBILE_OPTIMIZATIONS', false),
    ],
    'transport' => [
        'force_relogin_on_change' => env('TRAVIAN_FORCE_RELOGIN_ON_TRANSPORT_CHANGE', true),
    ],
    'defaults' => [
        // Default resource priority weights for new account settings: wood, clay, iron, crop.
        'account_resource_priorities' => env('TRAVIAN_DEFAULT_ACCOUNT_RESOURCE_PRIORITIES', '15,11,1,1'),
    ],
    'game' => [
        // Travian merchant carrying capacity by tribe. These are game rules, not user preferences.
        'merchant_capacity' => [
            'roman' => 500,
            'teuton' => 1000,
            'gaul' => 750,
        ],
    ],
    'proxy_pool' => [
        // Consecutive connection failures before a proxy rests and the account tries another one.
        'failure_threshold' => (int) env('TRAVIAN_PROXY_FAILURE_THRESHOLD', 5),
        // Rest time in minutes before a cooled proxy becomes eligible again.
        'cooldown_minutes' => (int) env('TRAVIAN_PROXY_COOLDOWN_MINUTES', 10),
        // Extra seconds added when every configured proxy is cooling.
        'all_cooling_retry_grace_seconds' => (int) env('TRAVIAN_PROXY_ALL_COOLING_RETRY_GRACE_SECONDS', 10),
        // Short retry after switching to another ready proxy.
        'switch_retry_minutes' => (int) env('TRAVIAN_PROXY_SWITCH_RETRY_MINUTES', 1),
    ],
    'automation' => [
        // Legacy/manual freshness hint. The smart automation path avoids full
        // dorf1/dorf2 refreshes unless required snapshots are missing or a
        // saved timer has elapsed.
        'snapshot_stale_minutes' => (int) env('TRAVIAN_AUTOMATION_SNAPSHOT_STALE_MINUTES', 10),
        'dispatcher_batch_size' => (int) env('TRAVIAN_AUTOMATION_DISPATCHER_BATCH_SIZE', 50),
        'idle_minutes' => (int) env('TRAVIAN_AUTOMATION_IDLE_MINUTES', 10),
        'timer_grace_seconds' => (int) env('TRAVIAN_AUTOMATION_TIMER_GRACE_SECONDS', 45),
        // Queue jobs must outlive slow proxy HTTP requests, otherwise accounts can remain in "syncing".
        'job_timeout_seconds' => (int) env('TRAVIAN_AUTOMATION_JOB_TIMEOUT_SECONDS', 90),
        // Local cleanup window for jobs killed before they can write a failed status.
        'stale_syncing_minutes' => (int) env('TRAVIAN_STALE_SYNCING_MINUTES', 5),
        // When Travian rejects a construction candidate for non-resource reasons, skip that exact candidate briefly.
        'build_page_block_cooldown_minutes' => (int) env('TRAVIAN_BUILD_PAGE_BLOCK_COOLDOWN_MINUTES', 10),
    ],
    'server' => [
        'timezone' => env('TRAVIAN_SERVER_TIMEZONE', 'Europe/London'),
    ],
    'debug' => [
        'dump_dorf1_response' => env('TRAVIAN_DEBUG_DUMP_DORF1_RESPONSE', false),
    ],
];
