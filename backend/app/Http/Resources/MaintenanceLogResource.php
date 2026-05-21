<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceLogResource extends JsonResource
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
            'maintenance_plan_id' => $this->maintenance_plan_id,
            'vault_id' => $this->vault_id,
            'device_id' => $this->device_id,
            'branch_id' => $this->branch_id,
            'performed_by' => $this->performed_by,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'duration_minutes' => $this->duration_minutes,
            'findings' => $this->findings,
            'actions_taken' => $this->actions_taken,
            'parts_replaced' => $this->parts_replaced,
            'next_maintenance_date' => $this->next_maintenance_date,
        ];
    }
}
