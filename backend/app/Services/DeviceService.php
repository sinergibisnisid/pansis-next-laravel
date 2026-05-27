<?php

namespace App\Services;

use App\DTOs\Device\RegisterDeviceDTO;
use App\DTOs\Device\HeartbeatDTO;
use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceService
{
    public function __construct(
        private readonly DeviceRepositoryInterface $deviceRepository,
        private readonly NotificationService $notificationService,
        private readonly AuditService $auditService,
    ) {}

    public function registerDevice(RegisterDeviceDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            $token = Str::random(64);

            $device = $this->deviceRepository->create([
                'vault_id' => $dto->vaultId,
                'branch_id' => $dto->branchId,
                'name' => $dto->name,
                'serial_number' => $dto->serialNumber,
                'type' => $dto->type,
                'ip_address' => $dto->ipAddress,
                'mac_address' => $dto->macAddress,
                'firmware_version' => $dto->firmwareVersion,
                'device_token' => hash('sha256', $token),
                'status' => DeviceStatus::Offline->value,
                'metadata' => $dto->metadata,
            ]);

            return [
                'device' => $device,
                'token' => $token,
            ];
        });
    }

    public function authenticateDevice(string $serialNumber, string $token): ?Device
    {
        $device = $this->deviceRepository->findBySerialNumber($serialNumber);

        if (!$device) {
            return null;
        }

        if (!hash_equals($device->device_token, hash('sha256', $token))) {
            return null;
        }

        return $device;
    }

    public function processHeartbeat(HeartbeatDTO $dto): Device
    {
        $device = $this->deviceRepository->findOrFail($dto->deviceId);

        $previousStatus = $device->status;

        $this->deviceRepository->updateHeartbeat($dto->deviceId, [
            'status' => $dto->status,
            'cpu_usage' => $dto->cpuUsage,
            'memory_usage' => $dto->memoryUsage,
            'temperature' => $dto->temperature,
            'signal_strength' => $dto->signalStrength,
            'uptime_seconds' => $dto->uptimeSeconds,
            'firmware_version' => $dto->firmwareVersion,
            'ip_address' => $dto->ipAddress,
            'error_count' => $dto->errorCount,
            'last_error' => $dto->lastError,
            'last_heartbeat_at' => now(),
        ]);

        // Persist a full heartbeat record (incl. network + power telemetry) for history.
        \App\Models\DeviceHeartbeat::create([
            'device_id' => $dto->deviceId,
            'status' => $dto->status,
            'cpu_usage' => $dto->cpuUsage,
            'memory_usage' => $dto->memoryUsage,
            'temperature' => $dto->temperature,
            'signal_strength' => $dto->signalStrength,
            'uptime_seconds' => $dto->uptimeSeconds,
            'firmware_version' => $dto->firmwareVersion,
            'ip_address' => $dto->ipAddress,
            'wan_status' => $dto->wanStatus,
            'isp_provider' => $dto->ispProvider,
            'vpn_connected' => $dto->vpnConnected,
            'vpn_endpoint' => $dto->vpnEndpoint,
            'ups_on_battery' => $dto->upsOnBattery,
            'ups_battery_percent' => $dto->upsBatteryPercent,
            'ups_runtime_minutes' => $dto->upsRuntimeMinutes,
            'error_count' => $dto->errorCount,
            'last_error' => $dto->lastError,
            'recorded_at' => now(),
        ]);

        // Detect critical infrastructure conditions and notify operators.
        $this->detectInfrastructureAlerts($device, $dto);

        // Detect status change from offline to online
        if ($previousStatus === DeviceStatus::Offline->value && $dto->status === DeviceStatus::Online->value) {
            \Illuminate\Support\Facades\Event::dispatch('device.online', ['device' => $device->fresh()]);
        }

        // Detect error state
        if ($dto->status === DeviceStatus::Error->value) {
            $this->notificationService->send(new \App\DTOs\Notification\SendNotificationDTO(
                branchId: $device->branch_id,
                channel: 'email',
                type: 'alert',
                title: "Device Error: {$device->name}",
                body: "Device {$device->serial_number} reported an error: {$dto->lastError}",
            ));
        }

        return $device->fresh();
    }

    /**
     * Notify operators when infrastructure conditions hit critical thresholds:
     *   - WAN went offline / failover.
     *   - VPN disconnected.
     *   - UPS running on battery with low remaining runtime.
     */
    private function detectInfrastructureAlerts(Device $device, HeartbeatDTO $dto): void
    {
        if ($dto->wanStatus === 'offline') {
            $this->notificationService->send(new \App\DTOs\Notification\SendNotificationDTO(
                branchId: $device->branch_id,
                channel: 'email',
                type: 'alert',
                title: "WAN Offline: {$device->name}",
                body: "Branch internet uplink is offline. Local execution continues, cloud sync paused.",
            ));
        }

        if ($dto->vpnConnected === false) {
            $this->notificationService->send(new \App\DTOs\Notification\SendNotificationDTO(
                branchId: $device->branch_id,
                channel: 'email',
                type: 'alert',
                title: "VPN Disconnected: {$device->name}",
                body: "VPN tunnel between branch controller and HQ is down.",
            ));
        }

        if ($dto->upsOnBattery === true && ($dto->upsBatteryPercent ?? 100) <= 20) {
            $this->notificationService->send(new \App\DTOs\Notification\SendNotificationDTO(
                branchId: $device->branch_id,
                channel: 'email',
                type: 'alert',
                title: "UPS Critical: {$device->name}",
                body: "Mains power is out and UPS battery is at {$dto->upsBatteryPercent}% (~{$dto->upsRuntimeMinutes} min remaining).",
            ));
        }
    }

    public function getDeviceStatus(string $deviceId): array
    {
        $device = $this->deviceRepository->findOrFail($deviceId);

        return [
            'device' => $device,
            'status' => $device->status,
            'last_heartbeat_at' => $device->last_heartbeat_at,
            'is_online' => $device->status === DeviceStatus::Online->value,
        ];
    }

    public function markOffline(string $deviceId): void
    {
        $device = $this->deviceRepository->findOrFail($deviceId);

        $device->update([
            'status' => DeviceStatus::Offline->value,
        ]);

        $this->notificationService->send(new \App\DTOs\Notification\SendNotificationDTO(
            branchId: $device->branch_id,
            channel: 'email',
            type: 'alert',
            title: "Device Offline: {$device->name}",
            body: "Device {$device->serial_number} has gone offline at " . now()->toDateTimeString(),
        ));

        \Illuminate\Support\Facades\Event::dispatch('device.offline', ['device' => $device]);
    }

    public function getOnlineDevices(?string $branchId = null): Collection
    {
        if ($branchId) {
            return $this->deviceRepository->getByBranch($branchId)
                ->where('status', DeviceStatus::Online->value);
        }

        return $this->deviceRepository->getOnlineDevices();
    }
}
