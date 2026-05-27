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
        // Vault logical events (high-level)
        'vault_open' => 'vault/+/open',
        'vault_close' => 'vault/+/close',
        'vault_alarm' => 'vault/+/alarm',
        'vault_emergency' => 'vault/+/emergency',

        // Door sensor events (physical state from controller)
        'door_opened' => 'door/+/opened',
        'door_closed' => 'door/+/closed',

        // Physical button events
        'button_exit_pressed' => 'button/+/exit_pressed',
        'button_emergency' => 'button/+/emergency',

        // Magnetic lock relay (commands published by backend, state reported by device)
        'lock_release' => 'lock/+/release',     // backend → device
        'lock_engage' => 'lock/+/engage',       // backend → device
        'lock_state' => 'lock/+/state',         // device → backend

        // Buzzer relay (commands published by backend, state reported by device)
        'buzzer_activate' => 'buzzer/+/activate',     // backend → device
        'buzzer_deactivate' => 'buzzer/+/deactivate', // backend → device
        'buzzer_state' => 'buzzer/+/state',           // device → backend

        // Fingerprint
        'fingerprint_scan' => 'fingerprint/+/scan',
        'fingerprint_register' => 'fingerprint/+/register',

        // Device health
        'device_heartbeat' => 'device/+/heartbeat',
        'device_status' => 'device/+/status',

        // Maintenance
        'maintenance_reminder' => 'maintenance/+/reminder',
    ],

    /**
     * Topic patterns the MqttSubscribeCommand should subscribe to (incoming only).
     * Outbound command topics (lock_release, buzzer_activate, etc.) are NOT in this list.
     */
    'subscribe_topics' => [
        'vault/+/open',
        'vault/+/close',
        'vault/+/alarm',
        'vault/+/emergency',
        'door/+/opened',
        'door/+/closed',
        'button/+/exit_pressed',
        'button/+/emergency',
        'lock/+/state',
        'buzzer/+/state',
        'fingerprint/+/scan',
        'fingerprint/+/register',
        'device/+/heartbeat',
        'device/+/status',
        'maintenance/+/reminder',
    ],

    'qos' => [
        'default' => 1,
        'alarm' => 2,
        'heartbeat' => 0,
        'door' => 1,
        'button' => 2,    // Buttons are safety-critical → exactly once
        'lock' => 2,      // Lock commands are safety-critical → exactly once
        'buzzer' => 1,
    ],
];
