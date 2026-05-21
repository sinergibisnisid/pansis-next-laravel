<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenancePlanResource extends JsonResource
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
            'assigned_to' => $this->assigned_to,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'frequency' => $this->frequency,
            'scheduled_date' => $this->scheduled_date,
            'scheduled_time' => $this->scheduled_time,
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'completed_by' => $this->completed_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at,

            // Conditional
            'vault' => new VaultResource($this->whenLoaded('vault')),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'logs' => MaintenanceLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
