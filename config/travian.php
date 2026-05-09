<?php

return [
    'http' => [
        'verify' => env('TRAVIAN_HTTP_VERIFY', true),
        'ca_bundle' => env('TRAVIAN_HTTP_CA_BUNDLE'),
        'timeout' => (int) env('TRAVIAN_HTTP_TIMEOUT', 20),
        'connect_timeout' => (int) env('TRAVIAN_HTTP_CONNECT_TIMEOUT', 10),
    ],
    'server' => [
        'timezone' => env('TRAVIAN_SERVER_TIMEZONE', 'Europe/London'),
    ],
    'debug' => [
        'dump_dorf1_response' => env('TRAVIAN_DEBUG_DUMP_DORF1_RESPONSE', false),
    ],
];
