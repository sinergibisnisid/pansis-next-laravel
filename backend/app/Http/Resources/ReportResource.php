<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'title' => $this->title,
            'type' => $this->type,
            'format' => $this->format,
            'status' => $this->status,
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'parameters' => $this->parameters,
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'generated_at' => $this->generated_at,
            'is_scheduled' => $this->is_scheduled,
            'schedule_frequency' => $this->schedule_frequency,
            'created_at' => $this->created_at,
            // file_path is intentionally excluded
        ];
    }
}
