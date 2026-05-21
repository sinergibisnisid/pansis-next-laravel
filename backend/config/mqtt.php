<?php

return [
    'host' => env('MQTT_HOST', 'localhost'),
    'port' => (int) env('MQTT_PORT', 1883),
    'client_id' => env('MQTT_CLIENT_ID', 'pansin-backend-' . env('APP_ENV', 'local')),
    'username' => env('MQTT_USERNAME'),
    'password' => env('MQTT_PASSWORD'),
    'keepalive' => (int) env('MQTT_KEEPALIVE', 60),
    'clean_session' => (bool) env('MQTT_CLEAN_SESSION', true),
    'use_tls' => (bool) env('MQTT_USE_TLS', false),
    'tls_port' => (int) env('MQTT_TLS_PORT', 8883),
    'reconnect' => [
        'enabled' => true,
        'max_attempts' => 10,
        'base_delay' => 1,
        'max_delay' => 60,
    ],
    'topics' => [
        'vault_open' => 'vault/+/open',
        'vault_close' => 'vault/+/close',
        'vault_alarm' => 'vault/+/alarm',
        'vault_emergency' => 'vault/+/emergency',
        'fingerprint_scan' => 'fingerprint/+/scan',
        'fingerprint_register' => 'fingerprint/+/register',
        'device_heartbeat' => 'device/+/heartbeat',
        'device_status' => 'device/+/status',
        'maintenance_reminder' => 'maintenance/+/reminder',
    ],
    'qos' => [
        'default' => 1,
        'alarm' => 2,
        'heartbeat' => 0,
    ],
];
