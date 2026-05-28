<?php

namespace App\Services;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Models\Device;
use App\Models\DeviceClaimCode;
use App\Models\DeviceMqttCredential;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Two-step device provisioning:
 *
 *  1. Admin calls generateClaimCode() from the dashboard.
 *     Returns the plaintext code ONCE — the dashboard must show it to the admin
 *     who then enters it on the IoT controller during initial setup.
 *
 *  2. Device boots and calls claimDevice() with (claim_code, serial_number,
 *     mac_address, [type, name]). On success it receives:
 *        - api_token      → used in X-Device-Token header for /api/v1/devices/*
 *        - mqtt_username  → broker login
 *        - mqtt_password  → broker login (plaintext, only returned once)
 *        - acl            → topic patterns the device is allowed to use
 *
 * For controllers, a default I/O channel layout is also provisioned via
 * ControllerChannelService::provisionDefaultLayout().
 */
class DeviceProvisioningService
{
    public function __construct(
        private readonly ControllerChannelService $controllerChannelService,
    ) {}

    /**
     * Step 1: Admin generates a claim code.
     *
     * @return array{claim_code: string, claim_code_id: string, expires_at: \Carbon\Carbon}
     */
    public function generateClaimCode(
        string $branchId,
        ?string $vaultId,
        DeviceType $expectedType,
        ?string $expectedName,
        User $admin,
        int $ttlMinutes = 60,
    ): array {
        // 8-character base32-ish alphanumeric code (excluding ambiguous I/O/0/1).
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $claim = DeviceClaimCode::create([
            'branch_id' => $branchId,
            'vault_id' => $vaultId,
            'created_by' => $admin->id,
            'code_hash' => hash('sha256', $code),
            'code_suffix' => substr($code, -4),
            'expected_device_type' => $expectedType->value,
            'expected_device_name' => $expectedName,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        return [
            'claim_code' => $code,           // shown ONCE, never again
            'claim_code_id' => $claim->id,
            'code_suffix' => $claim->code_suffix,
            'expires_at' => $claim->expires_at,
        ];
    }

    /**
     * Step 2: Device claims itself with the code.
     *
     * @return array{
     *   device: Device,
     *   api_token: string,
     *   mqtt_username: string,
     *   mqtt_password: string,
     *   publish_acl: array,
     *   subscribe_acl: array
     * }
     */
    public function claimDevice(
        string $code,
        string $serialNumber,
        ?string $macAddress = null,
        ?string $ipAddress = null,
        ?string $firmwareVersion = null,
    ): array {
        return DB::transaction(function () use ($code, $serialNumber, $macAddress, $ipAddress, $firmwareVersion) {
            $codeHash = hash('sha256', $code);

            $claim = DeviceClaimCode::query()
                ->where('code_hash', $codeHash)
                ->lockForUpdate()
                ->first();

            if (!$claim) {
                throw ValidationException::withMessages([
                    'claim_code' => ['Invalid claim code.'],
                ]);
            }

            if (!$claim->isUsable()) {
                throw ValidationException::withMessages([
                    'claim_code' => [$claim->isExpired() ? 'Claim code expired.' : 'Claim code already used.'],
                ]);
            }

            // Don't allow reusing a serial that already belongs to a different device
            // in another branch.
            $existing = Device::where('serial_number', $serialNumber)->first();
            if ($existing && $existing->branch_id !== $claim->branch_id) {
                throw ValidationException::withMessages([
                    'serial_number' => ['Serial already registered to a different branch.'],
                ]);
            }

            // Either reuse an existing pre-registered Device row (admin pre-created it)
            // or create a new one.
            $device = $existing ?? Device::create([
                'branch_id' => $claim->branch_id,
                'vault_id' => $claim->vault_id,
                'name' => $claim->expected_device_name ?? "{$claim->expected_device_type->value}-{$serialNumber}",
                'serial_number' => $serialNumber,
                'type' => $claim->expected_device_type,
                'status' => DeviceStatus::Offline,
                'is_active' => true,
            ]);

            // Update fields from the claim payload.
            $apiTokenPlaintext = Str::random(64);
            $device->update([
                'mac_address' => $macAddress,
                'ip_address' => $ipAddress,
                'firmware_version' => $firmwareVersion,
                'device_token' => hash('sha256', $apiTokenPlaintext),
                'is_active' => true,
            ]);

            // Issue MQTT credentials.
            $mqttUsername = "device_{$serialNumber}";
            $mqttPasswordPlaintext = Str::random(48);

            // Revoke any previous active credentials on this device (rotation).
            DeviceMqttCredential::where('device_id', $device->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $publishAcl = $this->buildPublishAcl($device);
            $subscribeAcl = $this->buildSubscribeAcl($device);

            DeviceMqttCredential::create([
                'device_id' => $device->id,
                'mqtt_username' => $mqttUsername,
                'mqtt_password_hash' => Hash::make($mqttPasswordPlaintext),
                'publish_acl' => $publishAcl,
                'subscribe_acl' => $subscribeAcl,
                'is_active' => true,
                'expires_at' => null, // long-lived; rotate manually if needed
            ]);

            // Provision default channel layout for controllers.
            if ($device->type === DeviceType::Controller) {
                $this->controllerChannelService->provisionDefaultLayout($device, $claim->vault_id);
            }

            // Mark the claim code as consumed.
            $claim->update([
                'used_at' => now(),
                'used_by_device_id' => $device->id,
            ]);

            return [
                'device' => $device->fresh(),
                'api_token' => $apiTokenPlaintext,
                'mqtt_username' => $mqttUsername,
                'mqtt_password' => $mqttPasswordPlaintext,
                'publish_acl' => $publishAcl,
                'subscribe_acl' => $subscribeAcl,
            ];
        });
    }

    /**
     * Verify a (mqtt_username, mqtt_password) pair. Used by the EMQX auth hook.
     */
    public function authenticateMqtt(string $username, string $password): ?DeviceMqttCredential
    {
        $credential = DeviceMqttCredential::where('mqtt_username', $username)
            ->where('is_active', true)
            ->first();

        if (!$credential || !$credential->isUsable()) {
            return null;
        }

        if (!Hash::check($password, $credential->mqtt_password_hash)) {
            return null;
        }

        $credential->update(['last_used_at' => now()]);

        return $credential;
    }

    /**
     * Check whether a device may publish/subscribe to the given topic.
     * Uses simple `+`/`#` wildcard matching à la MQTT.
     */
    public function isTopicAllowed(DeviceMqttCredential $credential, string $topic, string $action): bool
    {
        $acl = $action === 'publish' ? $credential->publish_acl : $credential->subscribe_acl;
        if (empty($acl)) {
            return false;
        }

        foreach ($acl as $pattern) {
            if ($this->mqttTopicMatches($pattern, $topic)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotate a device's MQTT credentials. Returns the new plaintext password (shown once).
     */
    public function rotateMqttCredentials(Device $device): array
    {
        return DB::transaction(function () use ($device) {
            $newPassword = Str::random(48);

            DeviceMqttCredential::where('device_id', $device->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $credential = DeviceMqttCredential::create([
                'device_id' => $device->id,
                'mqtt_username' => "device_{$device->serial_number}",
                'mqtt_password_hash' => Hash::make($newPassword),
                'publish_acl' => $this->buildPublishAcl($device),
                'subscribe_acl' => $this->buildSubscribeAcl($device),
                'is_active' => true,
            ]);

            return [
                'credential' => $credential,
                'mqtt_password' => $newPassword,
            ];
        });
    }

    /**
     * Build the publish ACL — controllers may publish hardware events for their
     * vault; cameras may publish snapshot events; etc.
     */
    private function buildPublishAcl(Device $device): array
    {
        $vaultScope = $device->vault_id ?? '+';

        return match ($device->type) {
            DeviceType::Controller => [
                "door/{$vaultScope}/opened",
                "door/{$vaultScope}/closed",
                "button/{$vaultScope}/exit_pressed",
                "button/{$vaultScope}/emergency",
                "lock/{$vaultScope}/state",
                "buzzer/{$vaultScope}/state",
                // P2-23: command ack topics — controller acks every lock/buzzer
                // command it executes so the backend can mark it acknowledged.
                "lock/{$vaultScope}/ack/+",
                "buzzer/{$vaultScope}/ack/+",
                "vault/{$vaultScope}/alarm",
                "vault/{$vaultScope}/emergency",
                "device/{$device->id}/heartbeat",
                "device/{$device->id}/status",
            ],
            DeviceType::FingerprintScanner => [
                "fingerprint/{$device->id}/scan",
                "fingerprint/{$device->id}/register",
                "device/{$device->id}/heartbeat",
                "device/{$device->id}/status",
            ],
            DeviceType::Camera => [
                "device/{$device->id}/heartbeat",
                "device/{$device->id}/status",
            ],
            default => [
                "device/{$device->id}/heartbeat",
                "device/{$device->id}/status",
            ],
        };
    }

    /**
     * Build the subscribe ACL — controllers must hear lock/buzzer commands
     * targeting their vault.
     */
    private function buildSubscribeAcl(Device $device): array
    {
        $vaultScope = $device->vault_id ?? '+';

        return match ($device->type) {
            DeviceType::Controller => [
                "lock/{$vaultScope}/release",
                "lock/{$vaultScope}/engage",
                "buzzer/{$vaultScope}/activate",
                "buzzer/{$vaultScope}/deactivate",
                "device/{$device->id}/command",
            ],
            DeviceType::FingerprintScanner => [
                "device/{$device->id}/command",
                "fingerprint/{$device->id}/sync",
            ],
            default => [
                "device/{$device->id}/command",
            ],
        };
    }

    /**
     * MQTT topic match with `+` (single segment) and `#` (multi segment) wildcards.
     */
    private function mqttTopicMatches(string $pattern, string $topic): bool
    {
        $patternParts = explode('/', $pattern);
        $topicParts = explode('/', $topic);

        $count = count($patternParts);
        for ($i = 0; $i < $count; $i++) {
            $p = $patternParts[$i];
            if ($p === '#') {
                return true;
            }
            if (!isset($topicParts[$i])) {
                return false;
            }
            if ($p === '+') {
                continue;
            }
            if ($p !== $topicParts[$i]) {
                return false;
            }
        }

        return count($patternParts) === count($topicParts);
    }
}
