<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'vault_id' => $this->vault_id,
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'serial_number' => $this->serial_number,
            'type' => $this->type,
            'status' => $this->status,
            'ip_address' => $this->ip_address,
            'mac_address' => $this->mac_address,
            'firmware_version' => $this->firmware_version,
            'signal_quality' => $this->signal_quality,
            'last_heartbeat_at' => $this->last_heartbeat_at,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,

            // Conditional
            'vault' => new VaultResource($this->whenLoaded('vault')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'latest_heartbeat' => new DeviceHeartbeatResource($this->whenLoaded('latestHeartbeat')),
        ];
    }
}
