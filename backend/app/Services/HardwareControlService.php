<?php

namespace App\Services;

use App\Enums\BuzzerState;
use App\Enums\LockState;
use App\Models\Vault;
use App\Repositories\Contracts\VaultRepositoryInterface;
use Illuminate\Support\Facades\Log;

/**
 * Publishes hardware command MQTT topics to the IoT controller.
 *
 * Per Pansin Access PDF "Intelligence Controller" + "Magnetic Lock" + "Alarm Buzzer":
 *   - Magnetic lock is controlled via relay output on the controller.
 *   - Buzzer is controlled via relay output on the controller.
 *   - Backend issues commands via MQTT; controller acknowledges by publishing
 *     state events back (e.g. lock/+/state, buzzer/+/state).
 *
 * Topic structure:
 *   lock/{vault_id}/release       → command: release magnetic lock
 *   lock/{vault_id}/engage        → command: re-engage magnetic lock
 *   lock/{vault_id}/state         → device → backend, current relay state
 *   buzzer/{vault_id}/activate    → command: turn buzzer on
 *   buzzer/{vault_id}/deactivate  → command: turn buzzer off
 *   buzzer/{vault_id}/state       → device → backend, current relay state
 */
class HardwareControlService
{
    public function __construct(
        private readonly MqttService $mqttService,
        private readonly VaultRepositoryInterface $vaultRepository,
    ) {}

    /**
     * Send the "release magnetic lock" command for the given vault.
     * Optimistically marks the vault lock_state as Released.
     */
    public function releaseLock(string $vaultId, ?string $userId = null, ?string $reason = null): bool
    {
        $payload = [
            'vault_id' => $vaultId,
            'user_id' => $userId,
            'reason' => $reason,
            'command' => 'release',
            'requested_at' => now()->toIso8601String(),
        ];

        $published = $this->mqttService->publish(
            topic: "lock/{$vaultId}/release",
            payload: $payload,
            qos: config('mqtt.qos.alarm', 2),
        );

        if ($published) {
            $this->vaultRepository->update($vaultId, [
                'lock_state' => LockState::Released->value,
            ]);
            Log::info('Lock release command published', $payload);
        } else {
            Log::error('Failed to publish lock release command', $payload);
        }

        return $published;
    }

    /**
     * Send the "engage magnetic lock" command for the given vault.
     */
    public function engageLock(string $vaultId, ?string $reason = null): bool
    {
        $payload = [
            'vault_id' => $vaultId,
            'reason' => $reason,
            'command' => 'engage',
            'requested_at' => now()->toIso8601String(),
        ];

        $published = $this->mqttService->publish(
            topic: "lock/{$vaultId}/engage",
            payload: $payload,
            qos: config('mqtt.qos.alarm', 2),
        );

        if ($published) {
            $this->vaultRepository->update($vaultId, [
                'lock_state' => LockState::Engaged->value,
            ]);
            Log::info('Lock engage command published', $payload);
        } else {
            Log::error('Failed to publish lock engage command', $payload);
        }

        return $published;
    }

    /**
     * Send the "activate buzzer" command for the given vault.
     */
    public function activateBuzzer(string $vaultId, ?string $reason = null, ?int $durationSeconds = null): bool
    {
        $payload = [
            'vault_id' => $vaultId,
            'reason' => $reason,
            'command' => 'activate',
            'duration_seconds' => $durationSeconds,
            'requested_at' => now()->toIso8601String(),
        ];

        $published = $this->mqttService->publish(
            topic: "buzzer/{$vaultId}/activate",
            payload: $payload,
            qos: config('mqtt.qos.alarm', 2),
        );

        if ($published) {
            $this->vaultRepository->update($vaultId, [
                'buzzer_state' => BuzzerState::On->value,
            ]);
            Log::info('Buzzer activate command published', $payload);
        } else {
            Log::error('Failed to publish buzzer activate command', $payload);
        }

        return $published;
    }

    /**
     * Send the "deactivate buzzer" command for the given vault.
     */
    public function deactivateBuzzer(string $vaultId, ?string $reason = null): bool
    {
        $payload = [
            'vault_id' => $vaultId,
            'reason' => $reason,
            'command' => 'deactivate',
            'requested_at' => now()->toIso8601String(),
        ];

        $published = $this->mqttService->publish(
            topic: "buzzer/{$vaultId}/deactivate",
            payload: $payload,
            qos: config('mqtt.qos.alarm', 2),
        );

        if ($published) {
            $this->vaultRepository->update($vaultId, [
                'buzzer_state' => BuzzerState::Off->value,
            ]);
            Log::info('Buzzer deactivate command published', $payload);
        } else {
            Log::error('Failed to publish buzzer deactivate command', $payload);
        }

        return $published;
    }

    /**
     * Acknowledge a state report from the device (lock/{id}/state, buzzer/{id}/state).
     * Updates the optimistic state we set when sending the command.
     */
    public function syncLockState(string $vaultId, LockState $state): void
    {
        $this->vaultRepository->update($vaultId, [
            'lock_state' => $state->value,
        ]);
    }

    public function syncBuzzerState(string $vaultId, BuzzerState $state): void
    {
        $this->vaultRepository->update($vaultId, [
            'buzzer_state' => $state->value,
        ]);
    }
}
