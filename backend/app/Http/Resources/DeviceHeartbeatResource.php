<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceHeartbeatResource extends JsonResource
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
            'device_id' => $this->device_id,
            'status' => $this->status,
            'cpu_usage' => $this->cpu_usage,
            'memory_usage' => $this->memory_usage,
            'temperature' => $this->temperature,
            'signal_strength' => $this->signal_strength,
            'uptime_seconds' => $this->uptime_seconds,
            'firmware_version' => $this->firmware_version,
            'ip_address' => $this->ip_address,
            'error_count' => $this->error_count,
            'last_error' => $this->last_error,
            'recorded_at' => $this->recorded_at,
        ];
    }
}
