<?php

namespace App\DTOs\Vault;

use Illuminate\Http\Request;

readonly class CreateVaultDTO
{
    public function __construct(
        public string $branchId,
        public string $name,
        public string $code,
        public string $type,
        public ?string $floor = null,
        public ?string $room = null,
        public ?int $maxSessionDurationMinutes = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            branchId: $request->input('branch_id'),
            name: $request->input('name'),
            code: $request->input('code'),
            type: $request->input('type'),
            floor: $request->input('floor'),
            room: $request->input('room'),
            maxSessionDurationMinutes: $request->input('max_session_duration_minutes'),
            metadata: $request->input('metadata'),
        );
    }

    public function toArray(): array
    {
        return [
            'branch_id' => $this->branchId,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'floor' => $this->floor,
            'room' => $this->room,
            'max_session_duration_minutes' => $this->maxSessionDurationMinutes,
            'metadata' => $this->metadata,
        ];
    }
}
