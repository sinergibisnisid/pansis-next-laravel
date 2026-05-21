<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkingTimeResource extends JsonResource
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
            'vault_id' => $this->vault_id,
            'name' => $this->name,
            'type' => $this->type,
            'day_of_week' => $this->day_of_week,
            'specific_date' => $this->specific_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'is_holiday' => $this->is_holiday,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
