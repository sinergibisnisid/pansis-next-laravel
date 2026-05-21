<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LivestreamSessionResource extends JsonResource
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
            'vault_id' => $this->vault_id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'stream_path' => $this->stream_path,
            'stream_url' => $this->stream_url,
            'webrtc_url' => $this->webrtc_url,
            'status' => $this->status,
            'started_at' => $this->started_at,
            'stopped_at' => $this->stopped_at,
            'duration_seconds' => $this->duration_seconds,
            'quality' => $this->quality,
            'created_at' => $this->created_at,
            // stream_token is intentionally excluded
        ];
    }
}
