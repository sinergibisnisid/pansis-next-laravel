<?php

namespace App\DTOs\Device;

use Illuminate\Http\Request;

readonly class HeartbeatDTO
{
    public function __construct(
        public string $deviceId,
        public string $status,
        public ?float $cpuUsage = null,
        public ?float $memoryUsage = null,
        public ?float $temperature = null,
        public ?int $signalStrength = null,
        public ?int $uptimeSeconds = null,
        public ?string $firmwareVersion = null,
        public ?string $ipAddress = null,
        public ?string $wanStatus = null,
        public ?string $ispProvider = null,
        public ?bool $vpnConnected = null,
        public ?string $vpnEndpoint = null,
        public ?bool $upsOnBattery = null,
        public ?int $upsBatteryPercent = null,
        public ?int $upsRuntimeMinutes = null,
        public ?int $errorCount = null,
        public ?string $lastError = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            deviceId: $request->input('device_id'),
            status: $request->input('status'),
            cpuUsage: $request->input('cpu_usage'),
            memoryUsage: $request->input('memory_usage'),
            temperature: $request->input('temperature'),
            signalStrength: $request->input('signal_strength'),
            uptimeSeconds: $request->input('uptime_seconds'),
            firmwareVersion: $request->input('firmware_version'),
            ipAddress: $request->ip(),
            wanStatus: $request->input('wan_status'),
            ispProvider: $request->input('isp_provider'),
            vpnConnected: $request->input('vpn_connected'),
            vpnEndpoint: $request->input('vpn_endpoint'),
            upsOnBattery: $request->input('ups_on_battery'),
            upsBatteryPercent: $request->input('ups_battery_percent'),
            upsRuntimeMinutes: $request->input('ups_runtime_minutes'),
            errorCount: $request->input('error_count'),
            lastError: $request->input('last_error'),
        );
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            deviceId: $payload['device_id'],
            status: $payload['status'] ?? 'healthy',
            cpuUsage: $payload['cpu_usage'] ?? null,
            memoryUsage: $payload['memory_usage'] ?? null,
            temperature: $payload['temperature'] ?? null,
            signalStrength: $payload['signal_strength'] ?? null,
            uptimeSeconds: $payload['uptime_seconds'] ?? null,
            firmwareVersion: $payload['firmware_version'] ?? null,
            ipAddress: $payload['ip_address'] ?? null,
            wanStatus: $payload['wan_status'] ?? null,
            ispProvider: $payload['isp_provider'] ?? null,
            vpnConnected: isset($payload['vpn_connected']) ? (bool) $payload['vpn_connected'] : null,
            vpnEndpoint: $payload['vpn_endpoint'] ?? null,
            upsOnBattery: isset($payload['ups_on_battery']) ? (bool) $payload['ups_on_battery'] : null,
            upsBatteryPercent: $payload['ups_battery_percent'] ?? null,
            upsRuntimeMinutes: $payload['ups_runtime_minutes'] ?? null,
            errorCount: $payload['error_count'] ?? null,
            lastError: $payload['last_error'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'status' => $this->status,
            'cpu_usage' => $this->cpuUsage,
            'memory_usage' => $this->memoryUsage,
            'temperature' => $this->temperature,
            'signal_strength' => $this->signalStrength,
            'uptime_seconds' => $this->uptimeSeconds,
            'firmware_version' => $this->firmwareVersion,
            'ip_address' => $this->ipAddress,
            'wan_status' => $this->wanStatus,
            'isp_provider' => $this->ispProvider,
            'vpn_connected' => $this->vpnConnected,
            'vpn_endpoint' => $this->vpnEndpoint,
            'ups_on_battery' => $this->upsOnBattery,
            'ups_battery_percent' => $this->upsBatteryPercent,
            'ups_runtime_minutes' => $this->upsRuntimeMinutes,
            'error_count' => $this->errorCount,
            'last_error' => $this->lastError,
        ];
    }
}
