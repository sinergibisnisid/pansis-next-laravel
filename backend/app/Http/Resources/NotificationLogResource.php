<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationLogResource extends JsonResource
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
            'channel' => $this->channel,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'recipient' => $this->recipient,
            'status' => $this->status,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'failed_at' => $this->failed_at,
            'failure_reason' => $this->failure_reason,
            'retry_count' => $this->retry_count,
            'created_at' => $this->created_at,
        ];
    }
}
