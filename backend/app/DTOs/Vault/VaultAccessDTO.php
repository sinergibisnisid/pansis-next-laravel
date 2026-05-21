<?php

namespace App\DTOs\Vault;

use Illuminate\Http\Request;

readonly class VaultAccessDTO
{
    public function __construct(
        public string $vaultId,
        public string $userId,
        public ?string $deviceId = null,
        public string $accessType = 'fingerprint',
        public ?string $fingerprintDeviceId = null,
        public ?float $confidenceScore = null,
        public ?string $ipAddress = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vaultId: $request->input('vault_id'),
            userId: $request->input('user_id'),
            deviceId: $request->input('device_id'),
            accessType: $request->input('access_type', 'fingerprint'),
            fingerprintDeviceId: $request->input('fingerprint_device_id'),
            confidenceScore: $request->input('confidence_score'),
            ipAddress: $request->ip(),
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'user_id' => $this->userId,
            'device_id' => $this->deviceId,
            'access_type' => $this->accessType,
            'fingerprint_device_id' => $this->fingerprintDeviceId,
            'confidence_score' => $this->confidenceScore,
            'ip_address' => $this->ipAddress,
        ];
    }
}
