<?php

return [
    'vault' => [
        'session_timeout_minutes' => (int) env('VAULT_SESSION_TIMEOUT_MINUTES', 10),
        'alarm_threshold_seconds' => (int) env('VAULT_ALARM_THRESHOLD_SECONDS', 600),
    ],

    'device' => [
        'heartbeat_interval' => (int) env('DEVICE_HEARTBEAT_INTERVAL', 30),
        'offline_threshold' => (int) env('DEVICE_OFFLINE_THRESHOLD', 90),
    ],

    'security' => [
        'ip_whitelist_enabled' => (bool) env('IP_WHITELIST_ENABLED', false),
        'ip_whitelist' => array_filter(explode(',', env('IP_WHITELIST', ''))),
        'max_login_attempts' => 5,
        'lockout_duration_minutes' => 30,
        'otp_expiry_minutes' => 5,
    ],

    'rate_limits' => [
        'api' => (int) env('API_RATE_LIMIT', 60),
        'login' => (int) env('LOGIN_RATE_LIMIT', 5),
        'otp' => (int) env('OTP_RATE_LIMIT', 3),
    ],

    'notifications' => [
        'whatsapp' => [
            'gateway_url' => env('WHATSAPP_GATEWAY_URL'),
            'token' => env('WHATSAPP_GATEWAY_TOKEN'),
            'sender' => env('WHATSAPP_GATEWAY_SENDER'),
        ],
    ],

    'mediamtx' => [
        'api_url' => env('MEDIAMTX_API_URL', 'http://localhost:9997'),
        'rtsp_url' => env('MEDIAMTX_RTSP_URL', 'rtsp://localhost:8554'),
        'webrtc_url' => env('MEDIAMTX_WEBRTC_URL', 'http://localhost:8889'),
    ],

    'cleanup' => [
        'heartbeats_days' => 30,
        'mqtt_logs_days' => 30,
        'snapshots_days' => 90,
        'server_monitoring_days' => 7,
    ],

    'metrics' => [
        'enabled' => (bool) env('METRICS_ENABLED', true),
        'route_prefix' => env('METRICS_ROUTE_PREFIX', 'metrics'),
    ],
];
