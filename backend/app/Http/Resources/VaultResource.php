<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VaultResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,
            'floor' => $this->floor,
            'room' => $this->room,
            'max_session_duration_minutes' => $this->max_session_duration_minutes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,

            // Conditional
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'active_session' => $this->when(
                $this->relationLoaded('sessions'),
                fn () => $this->sessions->where('status', 'active')->first()
                    ? new VaultSessionResource($this->sessions->where('status', 'active')->first())
                    : null
            ),
        ];
    }
}
