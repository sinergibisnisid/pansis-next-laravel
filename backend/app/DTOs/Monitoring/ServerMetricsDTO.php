<?php

namespace App\DTOs\Monitoring;

use Illuminate\Http\Request;

readonly class ServerMetricsDTO
{
    public function __construct(
        public string $hostname,
        public float $cpuUsage,
        public float $memoryUsage,
        public ?int $memoryTotalMb = null,
        public ?int $memoryUsedMb = null,
        public ?float $diskUsage = null,
        public ?int $diskTotalGb = null,
        public ?int $diskUsedGb = null,
        public ?int $queueSize = null,
        public ?int $queueFailed = null,
        public ?int $websocketConnections = null,
        public ?bool $mqttConnected = null,
        public ?int $mqttMessagesIn = null,
        public ?int $mqttMessagesOut = null,
        public ?int $activeStreams = null,
        public ?int $uptimeSeconds = null,
        public ?array $loadAverage = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            hostname: $request->input('hostname'),
            cpuUsage: $request->input('cpu_usage'),
            memoryUsage: $request->input('memory_usage'),
            memoryTotalMb: $request->input('memory_total_mb'),
            memoryUsedMb: $request->input('memory_used_mb'),
            diskUsage: $request->input('disk_usage'),
            diskTotalGb: $request->input('disk_total_gb'),
            diskUsedGb: $request->input('disk_used_gb'),
            queueSize: $request->input('queue_size'),
            queueFailed: $request->input('queue_failed'),
            websocketConnections: $request->input('websocket_connections'),
            mqttConnected: $request->input('mqtt_connected'),
            mqttMessagesIn: $request->input('mqtt_messages_in'),
            mqttMessagesOut: $request->input('mqtt_messages_out'),
            activeStreams: $request->input('active_streams'),
            uptimeSeconds: $request->input('uptime_seconds'),
            loadAverage: $request->input('load_average'),
        );
    }

    public function toArray(): array
    {
        return [
            'hostname' => $this->hostname,
            'cpu_usage' => $this->cpuUsage,
            'memory_usage' => $this->memoryUsage,
            'memory_total_mb' => $this->memoryTotalMb,
            'memory_used_mb' => $this->memoryUsedMb,
            'disk_usage' => $this->diskUsage,
            'disk_total_gb' => $this->diskTotalGb,
            'disk_used_gb' => $this->diskUsedGb,
            'queue_size' => $this->queueSize,
            'queue_failed' => $this->queueFailed,
            'websocket_connections' => $this->websocketConnections,
            'mqtt_connected' => $this->mqttConnected,
            'mqtt_messages_in' => $this->mqttMessagesIn,
            'mqtt_messages_out' => $this->mqttMessagesOut,
            'active_streams' => $this->activeStreams,
            'uptime_seconds' => $this->uptimeSeconds,
            'load_average' => $this->loadAverage,
        ];
    }
}
