<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SnapshotResource extends JsonResource
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
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'trigger_type' => $this->trigger_type,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'url' => $this->url,
            'captured_at' => $this->captured_at,
            'created_at' => $this->created_at,
        ];
    }
}
