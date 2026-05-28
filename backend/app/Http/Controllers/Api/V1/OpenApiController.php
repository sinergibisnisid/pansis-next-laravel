<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Serves the OpenAPI 3.1 specification + Swagger UI for the Pansin Access API.
 *
 *   GET /api/openapi.json   — machine-readable spec
 *   GET /api/docs           — interactive Swagger UI (no auth required by default)
 *
 * The spec is generated programmatically below so it stays in sync with the
 * actual route layout. For dev convenience we keep it source-authored rather
 * than relying on a third-party generator dependency.
 */
class OpenApiController extends Controller
{
    /**
     * GET /api/openapi.json
     */
    public function spec(): JsonResponse
    {
        return response()->json($this->buildSpec());
    }

    /**
     * GET /api/docs — minimal Swagger UI shell that loads the spec.
     */
    public function docs(): Response
    {
        $specUrl = url('/api/openapi.json');
        $title = 'PANSIN ACCESS — API Docs';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$title}</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>body{margin:0;padding:0;background:#fafafa}</style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
window.onload = function () {
    window.ui = SwaggerUIBundle({
        url: "{$specUrl}",
        dom_id: "#swagger-ui",
        deepLinking: true,
        persistAuthorization: true,
        layout: "BaseLayout"
    });
};
</script>
</body>
</html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    /**
     * Builds the OpenAPI 3.1 document.
     *
     * Kept as a single method so we can curate the public surface area
     * deliberately rather than auto-export every internal route.
     */
    private function buildSpec(): array
    {
        $version = config('app.version', '1.0.0');
        $serverUrl = config('app.url', 'http://localhost') . '/api';

        return [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'PANSIN ACCESS API',
                'version' => $version,
                'description' => 'Bank BJB Smart Vault Monitoring System backend API. '
                    . 'Realtime IoT vault access control, biometric auth, livestream surveillance, '
                    . 'audit trail, and centralized multi-branch monitoring.',
                'contact' => [
                    'name' => 'Pansin Access Team',
                    'email' => 'pansin-support@bankbjb.co.id',
                ],
                'license' => ['name' => 'Proprietary'],
            ],
            'servers' => [
                ['url' => $serverUrl, 'description' => 'Default'],
            ],
            'tags' => [
                ['name' => 'Health', 'description' => 'Liveness / readiness probes'],
                ['name' => 'Auth', 'description' => 'Login, OTP, token management'],
                ['name' => 'Users', 'description' => 'User management'],
                ['name' => 'Monitoring', 'description' => 'Dashboard, vault status, alarms, server health'],
                ['name' => 'Devices', 'description' => 'Device CRUD, heartbeat, provisioning, MQTT auth'],
                ['name' => 'Reports', 'description' => 'Report generation and export'],
                ['name' => 'Settings', 'description' => 'System configuration'],
                ['name' => 'P2 — Routers', 'description' => 'Router PoE / network gateway monitoring'],
                ['name' => 'P2 — UPS', 'description' => 'Uninterruptible power supply monitoring'],
                ['name' => 'P2 — Branch Geo', 'description' => 'Geo-aware multi-branch monitoring'],
                ['name' => 'P2 — Emergency', 'description' => 'Emergency button events + handling'],
                ['name' => 'P2 — Occupancy', 'description' => 'Vault occupancy tracking'],
                ['name' => 'P2 — Hardware Commands', 'description' => 'Reliable lock/buzzer command queue'],
                ['name' => 'P2 — Notifications', 'description' => 'Notification configuration'],
            ],
            'security' => [['BearerAuth' => []]],
            'components' => $this->components(),
            'paths' => $this->paths(),
        ];
    }

    private function components(): array
    {
        return [
            'securitySchemes' => [
                'BearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'Sanctum Token',
                    'description' => 'Obtain via POST /v1/auth/login.',
                ],
                'DeviceAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-Device-Token',
                    'description' => 'Issued at device provisioning time. Pair with X-Device-Serial.',
                ],
            ],
            'schemas' => [
                'Envelope' => [
                    'type' => 'object',
                    'required' => ['success', 'message'],
                    'properties' => [
                        'success' => ['type' => 'boolean'],
                        'message' => ['type' => 'string'],
                        'data' => ['nullable' => true],
                        'meta' => ['type' => 'object', 'nullable' => true],
                        'errors' => ['type' => 'object', 'nullable' => true],
                    ],
                ],
                'Error' => [
                    'type' => 'object',
                    'required' => ['success', 'message'],
                    'properties' => [
                        'success' => ['type' => 'boolean', 'enum' => [false]],
                        'message' => ['type' => 'string'],
                        'errors' => ['type' => 'object'],
                    ],
                ],
                'HealthSummary' => [
                    'type' => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['ok', 'degraded', 'down']],
                        'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                        'version' => ['type' => 'string'],
                        'environment' => ['type' => 'string'],
                        'services' => ['type' => 'object'],
                    ],
                ],
                'LoginRequest' => [
                    'type' => 'object',
                    'required' => ['login', 'password'],
                    'properties' => [
                        'login' => ['type' => 'string', 'description' => 'Username or email'],
                        'password' => ['type' => 'string', 'format' => 'password'],
                        'device_name' => ['type' => 'string', 'nullable' => true],
                    ],
                ],
                'Vault' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'format' => 'uuid'],
                        'branch_id' => ['type' => 'string', 'format' => 'uuid'],
                        'name' => ['type' => 'string'],
                        'code' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['main', 'secondary', 'atm', 'safe_deposit']],
                        'status' => ['type' => 'string', 'enum' => ['locked', 'unlocked', 'maintenance', 'alarm']],
                        'door_state' => ['type' => 'string', 'enum' => ['closed', 'opened', 'unknown']],
                        'lock_state' => ['type' => 'string', 'enum' => ['engaged', 'released', 'unknown']],
                        'buzzer_state' => ['type' => 'string', 'enum' => ['on', 'off']],
                        'max_session_duration_minutes' => ['type' => 'integer'],
                        'is_active' => ['type' => 'boolean'],
                    ],
                ],
                'AlarmLog' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'format' => 'uuid'],
                        'vault_id' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                        'branch_id' => ['type' => 'string', 'format' => 'uuid'],
                        'alarm_type' => ['type' => 'string'],
                        'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                        'status' => ['type' => 'string', 'enum' => ['active', 'acknowledged', 'resolved', 'false_alarm']],
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string', 'nullable' => true],
                        'triggered_at' => ['type' => 'string', 'format' => 'date-time'],
                    ],
                ],
                'HardwareCommand' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'format' => 'uuid'],
                        'vault_id' => ['type' => 'string', 'format' => 'uuid'],
                        'command_type' => ['type' => 'string', 'enum' => [
                            'lock_release', 'lock_engage', 'buzzer_activate',
                            'buzzer_deactivate', 'strobe_on', 'strobe_off',
                        ]],
                        'status' => ['type' => 'string', 'enum' => [
                            'pending', 'sent', 'acknowledged', 'failed', 'cancelled',
                        ]],
                        'attempts' => ['type' => 'integer'],
                        'max_attempts' => ['type' => 'integer'],
                        'first_sent_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                        'acknowledged_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                        'failed_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    ],
                ],
                'DeviceClaimRequest' => [
                    'type' => 'object',
                    'required' => ['claim_code', 'serial_number'],
                    'properties' => [
                        'claim_code' => ['type' => 'string', 'minLength' => 8, 'maxLength' => 8],
                        'serial_number' => ['type' => 'string'],
                        'mac_address' => ['type' => 'string', 'nullable' => true],
                        'firmware_version' => ['type' => 'string', 'nullable' => true],
                    ],
                ],
                'OfflineEventBatch' => [
                    'type' => 'object',
                    'required' => ['events'],
                    'properties' => [
                        'events' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'required' => ['source_event_id', 'topic', 'event_type', 'occurred_at'],
                                'properties' => [
                                    'source_event_id' => ['type' => 'string', 'format' => 'uuid'],
                                    'topic' => ['type' => 'string'],
                                    'event_type' => ['type' => 'string'],
                                    'vault_id' => ['type' => 'string', 'format' => 'uuid', 'nullable' => true],
                                    'occurred_at' => ['type' => 'string', 'format' => 'date-time'],
                                    'payload' => ['type' => 'object'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'Unauthenticated' => [
                    'description' => 'Missing or invalid bearer token',
                    'content' => ['application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ]],
                ],
                'Forbidden' => [
                    'description' => 'Authenticated but lacks required permission',
                    'content' => ['application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ]],
                ],
                'NotFound' => [
                    'description' => 'Resource not found',
                    'content' => ['application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ]],
                ],
                'ValidationError' => [
                    'description' => 'Validation failed',
                    'content' => ['application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/Error'],
                    ]],
                ],
            ],
        ];
    }

    private function paths(): array
    {
        return [
            // Health
            '/health' => ['get' => $this->op('Health', 'liveness',
                'Basic liveness probe. Returns 200 if the app process is up.', false)],
            '/health/ready' => ['get' => $this->op('Health', 'readiness',
                'Aggregated readiness probe across DB, Redis, MQTT, queue, storage.', false,
                response: ['$ref' => '#/components/schemas/HealthSummary'])],
            '/health/db' => ['get' => $this->op('Health', 'database',
                'Postgres connectivity + pool stats.', false)],
            '/health/redis' => ['get' => $this->op('Health', 'redis',
                'Redis ping + memory + connected clients.', false)],
            '/health/queue' => ['get' => $this->op('Health', 'queue',
                'Queue pending + failed counts.', false)],
            '/health/mqtt' => ['get' => $this->op('Health', 'mqtt',
                'MQTT broker reachability.', false)],
            '/health/storage' => ['get' => $this->op('Health', 'storage',
                'Filesystem write/read/delete probe.', false)],

            // Auth
            '/v1/auth/login' => ['post' => $this->op('Auth', 'login',
                'Username/email + password login. Returns Sanctum token.', false,
                requestSchema: ['$ref' => '#/components/schemas/LoginRequest'])],
            '/v1/auth/logout' => ['post' => $this->op('Auth', 'logout',
                'Revoke current Sanctum token.')],
            '/v1/auth/logout-all' => ['post' => $this->op('Auth', 'logoutAll',
                'Revoke all tokens for the current user.')],
            '/v1/auth/me' => ['get' => $this->op('Auth', 'me',
                'Get authenticated user with roles + permissions.')],
            '/v1/auth/refresh-token' => ['post' => $this->op('Auth', 'refresh',
                'Issue a fresh token (rotates the current one).')],
            '/v1/auth/send-otp' => ['post' => $this->op('Auth', 'sendOtp',
                'Send WhatsApp OTP to the authenticated user.')],
            '/v1/auth/verify-otp' => ['post' => $this->op('Auth', 'verifyOtp',
                'Verify the WhatsApp OTP code.')],

            // Devices
            '/v1/devices' => ['get' => $this->op('Devices', 'list',
                'List devices (paginated).')],
            '/v1/devices/heartbeat' => ['post' => $this->op('Devices', 'heartbeat',
                'Submit a device heartbeat. Authenticated via X-Device-Serial + X-Device-Token.',
                deviceAuth: true)],
            '/v1/devices/sync/events' => ['post' => $this->op('Devices', 'syncEvents',
                'Upload a batch of buffered events captured while offline. (P1-11)',
                deviceAuth: true,
                requestSchema: ['$ref' => '#/components/schemas/OfflineEventBatch'])],
            '/v1/devices/provision/codes' => ['post' => $this->op('Devices', 'generateClaimCode',
                'Admin generates a one-time provisioning code. (P1-12)')],
            '/v1/devices/provision/claim' => ['post' => $this->op('Devices', 'claimDevice',
                'Device claims itself with a code. Returns api_token + MQTT credentials. (P1-12)',
                public: true,
                requestSchema: ['$ref' => '#/components/schemas/DeviceClaimRequest'])],
            '/v1/devices/{device}/rotate-mqtt' => ['post' => $this->op('Devices', 'rotateMqtt',
                'Rotate a device\'s MQTT credentials.')],

            // MQTT broker hooks
            '/v1/mqtt/auth' => ['post' => $this->op('Devices', 'mqttAuth',
                'EMQX HTTP auth hook. Verifies (mqtt_username, mqtt_password) pairs.', false)],
            '/v1/mqtt/acl' => ['post' => $this->op('Devices', 'mqttAcl',
                'EMQX HTTP ACL hook. Authorizes per-device topic publish/subscribe.', false)],

            // Monitoring
            '/v1/monitoring/dashboard' => ['get' => $this->op('Monitoring', 'dashboard',
                'Aggregated dashboard data: vault counts, device statuses, active alarms, sessions.')],
            '/v1/monitoring/vaults' => ['get' => $this->op('Monitoring', 'vaultStatus',
                'All vaults with current logical + hardware status.')],
            '/v1/monitoring/alarms' => ['get' => $this->op('Monitoring', 'alarms',
                'Paginated alarm log with filtering (branch, severity, status, date).')],
            '/v1/monitoring/alarms/{id}/acknowledge' => ['post' => $this->op('Monitoring', 'acknowledgeAlarm',
                'Mark an alarm as acknowledged.')],
            '/v1/monitoring/alarms/{id}/resolve' => ['post' => $this->op('Monitoring', 'resolveAlarm',
                'Mark an alarm as resolved with resolution notes.')],
            '/v1/monitoring/server-health' => ['get' => $this->op('Monitoring', 'serverHealth',
                'Latest server metrics + health summary.')],
            '/v1/monitoring/prometheus' => ['get' => $this->op('Monitoring', 'prometheus',
                'Prometheus-format metrics endpoint.', false)],

            // Vault control
            '/v1/vaults/{id}/open' => ['post' => $this->op('Monitoring', 'openVault',
                'Approve vault access. Validates fingerprint + working hours, releases magnetic lock.')],
            '/v1/vaults/{id}/close' => ['post' => $this->op('Monitoring', 'closeVault',
                'Manually close a vault session.')],
            '/v1/vaults/{id}/sessions' => ['get' => $this->op('Monitoring', 'vaultSessions',
                'List sessions for a vault.')],

            // P2 — Routers
            '/v1/routers' => ['get' => $this->op('P2 — Routers', 'list',
                'List router devices + spec.')],
            '/v1/routers/summary' => ['get' => $this->op('P2 — Routers', 'summary',
                'Fleet summary: total, online/offline, vpn issues, on-failover.')],
            '/v1/routers/failover-active' => ['get' => $this->op('P2 — Routers', 'failoverActive',
                'List branches currently running on backup uplink.')],
            '/v1/routers/vpn-issues' => ['get' => $this->op('P2 — Routers', 'vpnIssues',
                'List branches with VPN tunnel down.')],

            // P2 — UPS
            '/v1/ups' => ['get' => $this->op('P2 — UPS', 'list',
                'List UPS devices + spec.')],
            '/v1/ups/summary' => ['get' => $this->op('P2 — UPS', 'summary',
                'Fleet summary: on-battery count, critical count, battery-replacement-due count.')],
            '/v1/ups/critical' => ['get' => $this->op('P2 — UPS', 'critical',
                'UPS units currently critical (<=20% on battery).')],
            '/v1/ups/battery-due' => ['get' => $this->op('P2 — UPS', 'batteryDue',
                'UPS units with battery replacement due.')],

            // P2 — Branch geo
            '/v1/branches/monitoring/geo' => ['get' => $this->op('P2 — Branch Geo', 'geoStatus',
                'Branches with lat/long + aggregated health for map view.')],
            '/v1/branches/monitoring/health' => ['get' => $this->op('P2 — Branch Geo', 'overallHealth',
                'Per-branch health roll-up.')],
            '/v1/branches/monitoring/with-alarms' => ['get' => $this->op('P2 — Branch Geo', 'withAlarms',
                'Branches that currently have one or more active alarms.')],

            // P2 — Emergency
            '/v1/emergencies/active' => ['get' => $this->op('P2 — Emergency', 'active',
                'Currently active emergency events.')],
            '/v1/emergencies/history' => ['get' => $this->op('P2 — Emergency', 'history',
                'Emergency event history (paginated).')],
            '/v1/emergencies/trigger' => ['post' => $this->op('P2 — Emergency', 'trigger',
                'Manually trigger an emergency from the dashboard.')],
            '/v1/emergencies/{id}/acknowledge' => ['post' => $this->op('P2 — Emergency', 'acknowledge',
                'Mark an emergency event as acknowledged.')],
            '/v1/emergencies/{id}/resolve' => ['post' => $this->op('P2 — Emergency', 'resolve',
                'Mark an emergency event as resolved.')],

            // P2 — Occupancy
            '/v1/occupancy/summary' => ['get' => $this->op('P2 — Occupancy', 'summary',
                'Active occupants per vault.')],
            '/v1/occupancy/vault/{vaultId}/status' => ['get' => $this->op('P2 — Occupancy', 'vaultStatus',
                'Current occupants in a vault.')],
            '/v1/occupancy/vault/{vaultId}/history' => ['get' => $this->op('P2 — Occupancy', 'vaultHistory',
                'Occupancy history for a vault.')],

            // P2 — Hardware commands
            '/v1/hardware-commands' => ['get' => $this->op('P2 — Hardware Commands', 'list',
                'List hardware commands with filtering.',
                response: ['$ref' => '#/components/schemas/HardwareCommand'])],
            '/v1/hardware-commands/dispatch' => ['post' => $this->op('P2 — Hardware Commands', 'dispatch',
                'Dispatch a hardware command (lock release/engage, buzzer on/off).')],
            '/v1/hardware-commands/{id}/cancel' => ['post' => $this->op('P2 — Hardware Commands', 'cancel',
                'Cancel a pending/sent hardware command.')],
            '/v1/hardware-commands/{id}/retry' => ['post' => $this->op('P2 — Hardware Commands', 'retry',
                'Manually retry a failed hardware command.')],
            '/v1/hardware-commands/statistics' => ['get' => $this->op('P2 — Hardware Commands', 'statistics',
                'Counts by command type and status.')],
        ];
    }

    /**
     * Build a single OpenAPI operation object.
     */
    private function op(
        string $tag,
        string $operationId,
        string $summary,
        bool $auth = true,
        bool $deviceAuth = false,
        bool $public = false,
        ?array $requestSchema = null,
        ?array $response = null,
    ): array {
        $op = [
            'tags' => [$tag],
            'operationId' => $operationId,
            'summary' => $summary,
            'responses' => [
                '200' => [
                    'description' => 'Success',
                    'content' => ['application/json' => [
                        'schema' => $response ?? ['$ref' => '#/components/schemas/Envelope'],
                    ]],
                ],
                '401' => ['$ref' => '#/components/responses/Unauthenticated'],
                '403' => ['$ref' => '#/components/responses/Forbidden'],
                '404' => ['$ref' => '#/components/responses/NotFound'],
                '422' => ['$ref' => '#/components/responses/ValidationError'],
            ],
        ];

        if ($public || !$auth) {
            $op['security'] = [];
        } elseif ($deviceAuth) {
            $op['security'] = [['DeviceAuth' => []]];
        }
        // else: defaults to BearerAuth from the global security stanza.

        if ($requestSchema) {
            $op['requestBody'] = [
                'required' => true,
                'content' => ['application/json' => ['schema' => $requestSchema]],
            ];
        }

        return $op;
    }
}
