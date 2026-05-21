<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaultSessionResource extends JsonResource
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
            'user_id' => $this->user_id,
            'device_id' => $this->device_id,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'duration_seconds' => $this->duration_seconds,
            'max_duration_seconds' => $this->max_duration_seconds,
            'status' => $this->status,
            'timeout_alarm_triggered' => $this->timeout_alarm_triggered,
            'close_reason' => $this->close_reason,
            'created_at' => $this->created_at,

            // Conditional
            'vault' => new VaultResource($this->whenLoaded('vault')),
            'user' => new UserResource($this->whenLoaded('user')),
            'device' => $this->whenLoaded('device'),
        ];
    }
}
