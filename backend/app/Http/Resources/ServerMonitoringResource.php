<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServerMonitoringResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hostname' => $this->hostname,
            'cpu_usage' => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'memory_total_mb' => $this->memory_total_mb,
            'memory_used_mb' => $this->memory_used_mb,
            'disk_usage' => $this->disk_usage,
            'disk_total_gb' => $this->disk_total_gb,
            'disk_used_gb' => $this->disk_used_gb,
            'queue_size' => $this->queue_size,
            'queue_failed' => $this->queue_failed,
            'websocket_connections' => $this->websocket_connections,
            'mqtt_connected' => $this->mqtt_connected,
            'active_streams' => $this->active_streams,
            'uptime_seconds' => $this->uptime_seconds,
            'load_average' => $this->load_average,
            'recorded_at' => $this->recorded_at,
        ];
    }
}
