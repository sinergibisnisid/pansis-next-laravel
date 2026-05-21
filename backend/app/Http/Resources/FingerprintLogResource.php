<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FingerprintLogResource extends JsonResource
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
            'fingerprint_device_id' => $this->fingerprint_device_id,
            'device_id' => $this->device_id,
            'user_id' => $this->user_id,
            'vault_id' => $this->vault_id,
            'scan_result' => $this->scan_result,
            'confidence_score' => $this->confidence_score,
            'rejection_reason' => $this->rejection_reason,
            'scanned_at' => $this->scanned_at,

            // Conditional
            'user' => new UserResource($this->whenLoaded('user')),
            'device' => new DeviceResource($this->whenLoaded('device')),
            'vault' => new VaultResource($this->whenLoaded('vault')),
        ];
    }
}
