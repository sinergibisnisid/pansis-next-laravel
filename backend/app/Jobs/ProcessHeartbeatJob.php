<?php

namespace App\Jobs;

use App\DTOs\Device\HeartbeatDTO;
use App\Events\DeviceHeartbeatReceived;
use App\Services\DeviceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHeartbeatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $deviceId,
        public readonly array $payload,
    ) {
        $this->onQueue('heartbeat');
    }

    public function handle(DeviceService $deviceService): void
    {
        $heartbeatDTO = new HeartbeatDTO(
            deviceId: $this->deviceId,
            status: $this->payload['status'] ?? 'online',
            cpuUsage: $this->payload['cpu_usage'] ?? null,
            memoryUsage: $this->payload['memory_usage'] ?? null,
            temperature: $this->payload['temperature'] ?? null,
            signalStrength: $this->payload['signal_strength'] ?? null,
            uptimeSeconds: $this->payload['uptime_seconds'] ?? null,
            firmwareVersion: $this->payload['firmware_version'] ?? null,
            ipAddress: $this->payload['ip_address'] ?? null,
            errorCount: $this->payload['error_count'] ?? null,
            lastError: $this->payload['last_error'] ?? null,
        );

        $deviceService->processHeartbeat($heartbeatDTO);

        event(new DeviceHeartbeatReceived(
            deviceId: $this->deviceId,
            payload: $this->payload,
        ));

        Log::info('Heartbeat processed successfully', [
            'device_id' => $this->deviceId,
        ]);
    }
}
