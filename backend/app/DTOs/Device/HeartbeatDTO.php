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
            errorCount: $request->input('error_count'),
            lastError: $request->input('last_error'),
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
            'error_count' => $this->errorCount,
            'last_error' => $this->lastError,
        ];
    }
}
