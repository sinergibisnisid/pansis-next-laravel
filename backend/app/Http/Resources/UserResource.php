<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,

            // Conditional relationships
            'roles' => $this->whenLoaded('roles'),
            'permissions' => $this->whenLoaded('permissions'),
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
        ];
    }
}
