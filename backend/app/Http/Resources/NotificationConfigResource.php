<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationConfigResource extends JsonResource
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
            'user_id' => $this->user_id,
            'branch_id' => $this->branch_id,
            'event_type' => $this->event_type,
            'channel' => $this->channel,
            'is_enabled' => $this->is_enabled,
            'recipients' => $this->recipients,
            'schedule' => $this->schedule,
            'template' => $this->template,
            'created_at' => $this->created_at,
        ];
    }
}
