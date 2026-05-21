<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlarmLogResource extends JsonResource
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
            'device_id' => $this->device_id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'alarm_type' => $this->alarm_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'title' => $this->title,
            'description' => $this->description,
            'acknowledged_by' => $this->acknowledged_by,
            'acknowledged_at' => $this->acknowledged_at,
            'resolved_by' => $this->resolved_by,
            'resolved_at' => $this->resolved_at,
            'resolution_notes' => $this->resolution_notes,
            'triggered_at' => $this->triggered_at,
            'created_at' => $this->created_at,

            // Conditional
            'vault' => new VaultResource($this->whenLoaded('vault')),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'user' => new UserResource($this->whenLoaded('user')),
            'acknowledged_by_user' => new UserResource($this->whenLoaded('acknowledgedByUser')),
            'resolved_by_user' => new UserResource($this->whenLoaded('resolvedByUser')),
        ];
    }
}
