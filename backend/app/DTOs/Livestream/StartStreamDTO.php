<?php

namespace App\DTOs\Livestream;

use Illuminate\Http\Request;

readonly class StartStreamDTO
{
    public function __construct(
        public string $deviceId,
        public string $vaultId,
        public ?string $branchId = null,
        public ?string $userId = null,
        public ?string $streamPath = null,
        public string $quality = 'medium',
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            deviceId: $request->input('device_id'),
            vaultId: $request->input('vault_id'),
            branchId: $request->input('branch_id'),
            userId: $request->input('user_id'),
            streamPath: $request->input('stream_path'),
            quality: $request->input('quality', 'medium'),
        );
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'vault_id' => $this->vaultId,
            'branch_id' => $this->branchId,
            'user_id' => $this->userId,
            'stream_path' => $this->streamPath,
            'quality' => $this->quality,
        ];
    }
}
