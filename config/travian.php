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
        // Atomic floor between two complete automation cycles for the same account.
        'minimum_cycle_seconds' => (int) env('TRAVIAN_AUTOMATION_MINIMUM_CYCLE_SECONDS', 60),
        // Full account cycles may cross several villages through a slow proxy.
        'job_timeout_seconds' => (int) env('TRAVIAN_AUTOMATION_JOB_TIMEOUT_SECONDS', 300),
        // Shared account-session locks must expire shortly after a killed worker, not remain orphaned for 30 minutes.
        'account_lock_expire_seconds' => (int) env('TRAVIAN_ACCOUNT_LOCK_EXPIRE_SECONDS', 360),
        // Manual/read-only syncs wait quietly for an active account session instead of polling aggressively.
        'account_lock_release_seconds' => (int) env('TRAVIAN_ACCOUNT_LOCK_RELEASE_SECONDS', 60),
        'sync_lock_wait_minutes' => (int) env('TRAVIAN_SYNC_LOCK_WAIT_MINUTES', 15),
        // Local cleanup window for jobs killed before they can write a failed status.
        'stale_syncing_minutes' => (int) env('TRAVIAN_STALE_SYNCING_MINUTES', 5),
        // When Travian rejects a construction candidate for non-resource reasons, skip that exact candidate briefly.
        'build_page_block_cooldown_minutes' => (int) env('TRAVIAN_BUILD_PAGE_BLOCK_COOLDOWN_MINUTES', 10),
    ],
    'runtime' => [
        'heartbeat_stale_seconds' => (int) env('TRAVIAN_RUNTIME_HEARTBEAT_STALE_SECONDS', 150),
        'heartbeat_interval_seconds' => (int) env('TRAVIAN_RUNTIME_HEARTBEAT_INTERVAL_SECONDS', 30),
        'supervisor_poll_seconds' => (int) env('TRAVIAN_RUNTIME_SUPERVISOR_POLL_SECONDS', 2),
        'queue_sleep_seconds' => (int) env('TRAVIAN_QUEUE_WORKER_SLEEP_SECONDS', 3),
        'queue_memory_mb' => (int) env('TRAVIAN_QUEUE_WORKER_MEMORY_MB', 512),
        'queue_max_time_seconds' => (int) env('TRAVIAN_QUEUE_WORKER_MAX_TIME_SECONDS', 3600),
        'host' => env('TRAVIAN_RUNTIME_HOST', '127.0.0.1'),
        'port' => (int) env('TRAVIAN_RUNTIME_PORT', 8000),
    ],
    'retention' => [
        'activity_log_days' => (int) env('TRAVIAN_ACTIVITY_LOG_RETENTION_DAYS', 7),
        'activity_log_max_rows' => (int) env('TRAVIAN_ACTIVITY_LOG_MAX_ROWS', 5000),
        'failed_job_days' => (int) env('TRAVIAN_FAILED_JOB_RETENTION_DAYS', 14),
        'failed_job_max_rows' => (int) env('TRAVIAN_FAILED_JOB_MAX_ROWS', 1000),
        'cleanup_batch_size' => (int) env('TRAVIAN_CLEANUP_BATCH_SIZE', 500),
        'cleanup_max_batches' => (int) env('TRAVIAN_CLEANUP_MAX_BATCHES', 100),
    ],
    'server' => [
        'timezone' => env('TRAVIAN_SERVER_TIMEZONE', 'Europe/London'),
    ],
    'debug' => [
        'dump_dorf1_response' => env('TRAVIAN_DEBUG_DUMP_DORF1_RESPONSE', false),
    ],
];
