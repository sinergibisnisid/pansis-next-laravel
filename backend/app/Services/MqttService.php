<?php

namespace App\Services;

use App\Enums\MqttDirection;
use App\Enums\MqttStatus;
use App\Models\MqttLog;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\Facades\MQTT;

class MqttService
{
    public function __construct(
        private readonly VaultService $vaultService,
        private readonly DeviceService $deviceService,
        private readonly SnapshotService $snapshotService,
        private readonly NotificationService $notificationService,
        private readonly VaultDoorService $vaultDoorService,
        private readonly HardwareControlService $hardwareControlService,
        private readonly HardwareCommandService $hardwareCommandService,
    ) {}

    public function publish(string $topic, array $payload, int $qos = 0): bool
    {
        try {
            $mqtt = MQTT::connection();
            $mqtt->publish($topic, json_encode($payload), $qos);

            $this->logMessage(
                topic: $topic,
                payload: $payload,
                direction: MqttDirection::Outgoing,
                deviceId: $payload['device_id'] ?? null,
                status: MqttStatus::Processed
            );

            return true;
        } catch (\Throwable $e) {
            Log::error("MQTT publish failed: {$e->getMessage()}", [
                'topic' => $topic,
                'payload' => $payload,
            ]);

            $this->logMessage(
                topic: $topic,
                payload: $payload,
                direction: MqttDirection::Outgoing,
                deviceId: $payload['device_id'] ?? null,
                status: MqttStatus::Failed
            );

            return false;
        }
    }

    public function subscribe(string $topic, callable $callback): void
    {
        try {
            $mqtt = MQTT::connection();
            $mqtt->subscribe($topic, function (string $topic, string $message) use ($callback) {
                $callback($topic, $message);
            }, 0);
            $mqtt->loop(true);
        } catch (\Throwable $e) {
            Log::error("MQTT subscribe failed: {$e->getMessage()}", [
                'topic' => $topic,
            ]);
        }
    }

    public function handleIncomingMessage(string $topic, string $payload): void
    {
        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("MQTT invalid JSON payload", [
                'topic' => $topic,
                'payload' => $payload,
            ]);
            return;
        }

        $this->logMessage(
            topic: $topic,
            payload: $data,
            direction: MqttDirection::Incoming,
            deviceId: $data['device_id'] ?? null,
            status: MqttStatus::Received
        );

        try {
            match (true) {
                str_starts_with($topic, 'door/') && str_ends_with($topic, '/opened')
                    => $this->processDoorOpened($data),
                str_starts_with($topic, 'door/') && str_ends_with($topic, '/closed')
                    => $this->processDoorClosed($data),
                str_starts_with($topic, 'button/') && str_ends_with($topic, '/exit_pressed')
                    => $this->processExitButton($data),
                str_starts_with($topic, 'button/') && str_ends_with($topic, '/emergency')
                    => $this->processEmergencyButton($data),
                str_starts_with($topic, 'lock/') && str_contains($topic, '/ack/')
                    => $this->processCommandAck($topic, $data),
                str_starts_with($topic, 'buzzer/') && str_contains($topic, '/ack/')
                    => $this->processCommandAck($topic, $data),
                str_starts_with($topic, 'lock/') && str_ends_with($topic, '/state')
                    => $this->processLockState($data),
                str_starts_with($topic, 'buzzer/') && str_ends_with($topic, '/state')
                    => $this->processBuzzerState($data),
                str_starts_with($topic, 'vault/open') => $this->processVaultOpen($data),
                str_starts_with($topic, 'vault/close') => $this->processVaultClose($data),
                str_starts_with($topic, 'vault/alarm') => $this->processVaultAlarm($data),
                str_starts_with($topic, 'vault/emergency') => $this->processEmergencyButton($data),
                str_starts_with($topic, 'fingerprint/scan') => $this->processFingerprintScan($data),
                str_starts_with($topic, 'device/heartbeat') => $this->processDeviceHeartbeat($data),
                str_starts_with($topic, 'device/status') => $this->processDeviceStatus($data),
                default => Log::warning("MQTT unhandled topic: {$topic}"),
            };

            // Update log status to processed
            MqttLog::where('topic', $topic)
                ->where('direction', MqttDirection::Incoming)
                ->latest()
                ->first()
                ?->update(['status' => MqttStatus::Processed->value, 'processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error("MQTT message processing failed: {$e->getMessage()}", [
                'topic' => $topic,
                'payload' => $data,
            ]);

            MqttLog::where('topic', $topic)
                ->where('direction', MqttDirection::Incoming)
                ->latest()
                ->first()
                ?->update([
                    'status' => MqttStatus::Failed->value,
                    'error_message' => $e->getMessage(),
                ]);
        }
    }

    public function processVaultOpen(array $payload): void
    {
        $dto = new \App\DTOs\Vault\VaultAccessDTO(
            vaultId: $payload['vault_id'],
            userId: $payload['user_id'],
            deviceId: $payload['device_id'] ?? null,
            accessType: $payload['access_type'] ?? 'fingerprint',
            fingerprintDeviceId: $payload['fingerprint_device_id'] ?? null,
            confidenceScore: $payload['confidence_score'] ?? null,
        );

        $this->vaultService->openVault($dto);
    }

    public function processVaultClose(array $payload): void
    {
        $dto = new \App\DTOs\Vault\CloseVaultDTO(
            vaultId: $payload['vault_id'],
            sessionId: $payload['session_id'],
            userId: $payload['user_id'],
            closeReason: $payload['close_reason'] ?? null,
        );

        $this->vaultService->closeVault($dto);
    }

    public function processVaultAlarm(array $payload): void
    {
        $vaultId = $payload['vault_id'];
        $alarmType = $payload['alarm_type'] ?? 'unknown';
        $deviceId = $payload['device_id'] ?? null;

        // Update vault status to alarm
        $this->vaultService->getVaultStatus($vaultId);

        // Trigger snapshot
        $this->snapshotService->captureSnapshot(
            $vaultId,
            $deviceId ?? '',
            null,
            \App\Enums\SnapshotTrigger::Alarm
        );

        // Send alarm notification
        $this->notificationService->sendUnauthorizedAccessAlert(
            $vaultId,
            $payload['user_id'] ?? null,
            "Alarm triggered: {$alarmType}"
        );

        // Dispatch event
        \Illuminate\Support\Facades\Event::dispatch('vault.alarm.triggered', [
            'vault_id' => $vaultId,
            'alarm_type' => $alarmType,
            'device_id' => $deviceId,
            'payload' => $payload,
        ]);
    }

    public function processFingerprintScan(array $payload): void
    {
        $deviceId = $payload['device_id'];
        $fingerprintId = $payload['fingerprint_id'] ?? null;
        $userId = $payload['user_id'] ?? null;

        // Dispatch event for fingerprint processing
        \Illuminate\Support\Facades\Event::dispatch('fingerprint.scanned', [
            'device_id' => $deviceId,
            'fingerprint_id' => $fingerprintId,
            'user_id' => $userId,
            'confidence_score' => $payload['confidence_score'] ?? null,
            'scan_result' => $payload['scan_result'] ?? null,
        ]);
    }

    public function processDeviceHeartbeat(array $payload): void
    {
        $dto = new \App\DTOs\Device\HeartbeatDTO(
            deviceId: $payload['device_id'],
            status: $payload['status'] ?? 'online',
            cpuUsage: $payload['cpu_usage'] ?? null,
            memoryUsage: $payload['memory_usage'] ?? null,
            temperature: $payload['temperature'] ?? null,
            signalStrength: $payload['signal_strength'] ?? null,
            uptimeSeconds: $payload['uptime_seconds'] ?? null,
            firmwareVersion: $payload['firmware_version'] ?? null,
            ipAddress: $payload['ip_address'] ?? null,
            errorCount: $payload['error_count'] ?? null,
            lastError: $payload['last_error'] ?? null,
        );

        $this->deviceService->processHeartbeat($dto);
    }

    public function processDeviceStatus(array $payload): void
    {
        $deviceId = $payload['device_id'];
        $status = $payload['status'] ?? null;

        if ($status === 'offline') {
            $this->deviceService->markOffline($deviceId);
        }

        \Illuminate\Support\Facades\Event::dispatch('device.status.changed', [
            'device_id' => $deviceId,
            'status' => $status,
            'payload' => $payload,
        ]);
    }

    /**
     * Handle door/{vault_id}/opened MQTT topic.
     * Per Pansin Access PDF: this is when the occupancy timer truly starts.
     */
    public function processDoorOpened(array $payload): void
    {
        $dto = \App\DTOs\Hardware\DoorSensorEventDTO::fromPayload(
            array_merge($payload, ['state' => 'opened'])
        );
        $this->vaultDoorService->handleDoorOpened($dto);
    }

    /**
     * Handle door/{vault_id}/closed MQTT topic.
     */
    public function processDoorClosed(array $payload): void
    {
        $dto = \App\DTOs\Hardware\DoorSensorEventDTO::fromPayload(
            array_merge($payload, ['state' => 'closed'])
        );
        $this->vaultDoorService->handleDoorClosed($dto);
    }

    /**
     * Handle button/{vault_id}/exit_pressed MQTT topic.
     */
    public function processExitButton(array $payload): void
    {
        $dto = \App\DTOs\Hardware\ButtonEventDTO::fromPayload(
            $payload,
            \App\Enums\ButtonType::Exit,
        );
        $this->vaultDoorService->handleExitButtonPressed($dto);
    }

    /**
     * Handle button/{vault_id}/emergency or vault/emergency MQTT topic.
     */
    public function processEmergencyButton(array $payload): void
    {
        $dto = \App\DTOs\Hardware\ButtonEventDTO::fromPayload(
            $payload,
            \App\Enums\ButtonType::Emergency,
        );
        $this->vaultDoorService->handleEmergencyButtonPressed($dto);
    }

    /**
     * Handle lock/{vault_id}/state MQTT topic — controller reports current relay state.
     */
    public function processLockState(array $payload): void
    {
        if (!isset($payload['vault_id'], $payload['state'])) {
            Log::warning('Invalid lock state payload', $payload);
            return;
        }

        $state = \App\Enums\LockState::tryFrom($payload['state']);
        if (!$state) {
            Log::warning('Unknown lock state', $payload);
            return;
        }

        $this->hardwareControlService->syncLockState($payload['vault_id'], $state);
    }

    /**
     * Handle buzzer/{vault_id}/state MQTT topic — controller reports current relay state.
     */
    public function processBuzzerState(array $payload): void
    {
        if (!isset($payload['vault_id'], $payload['state'])) {
            Log::warning('Invalid buzzer state payload', $payload);
            return;
        }

        $state = \App\Enums\BuzzerState::tryFrom($payload['state']);
        if (!$state) {
            Log::warning('Unknown buzzer state', $payload);
            return;
        }

        $this->hardwareControlService->syncBuzzerState($payload['vault_id'], $state);
    }

    /**
     * Handle lock/{vault_id}/ack/{command_id} or buzzer/{vault_id}/ack/{command_id}.
     * Controller acknowledges execution of a hardware command.
     *
     * Payload: { "status": "success" | "error", "error": "...optional..." }
     */
    public function processCommandAck(string $topic, array $payload): void
    {
        // Topic shape: "{lock|buzzer}/{vault_id}/ack/{command_id}"
        $parts = explode('/', $topic);
        if (count($parts) < 4) {
            Log::warning('Malformed ack topic', ['topic' => $topic]);
            return;
        }

        $commandId = $parts[3];
        $status = $payload['status'] ?? 'error';
        $error = $payload['error'] ?? null;

        $this->hardwareCommandService->handleAck($commandId, $status, $error);
    }

    public function logMessage(
        string $topic,
        array $payload,
        MqttDirection $direction,
        ?string $deviceId,
        MqttStatus $status
    ): void {
        MqttLog::create([
            'topic' => $topic,
            'payload' => $payload,
            'direction' => $direction,
            'device_id' => $deviceId,
            'qos' => 0,
            'retained' => false,
            'status' => $status,
            'processed_at' => $status === MqttStatus::Processed ? now() : null,
        ]);
    }
}
