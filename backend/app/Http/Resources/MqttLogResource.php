<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MqttLogResource extends JsonResource
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
            'topic' => $this->topic,
            'payload' => $this->payload,
            'direction' => $this->direction,
            'device_id' => $this->device_id,
            'qos' => $this->qos,
            'retained' => $this->retained,
            'status' => $this->status,
            'error_message' => $this->error_message,
            'processed_at' => $this->processed_at,
            'created_at' => $this->created_at,
        ];
    }
}
