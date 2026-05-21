<?php

namespace App\DTOs\Fingerprint;

use Illuminate\Http\Request;

readonly class FingerprintScanDTO
{
    public function __construct(
        public string $deviceId,
        public string $fingerprintId,
        public string $userId,
        public string $vaultId,
        public ?float $confidenceScore = null,
        public ?string $ipAddress = null,
        public ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            deviceId: $request->input('device_id'),
            fingerprintId: $request->input('fingerprint_id'),
            userId: $request->input('user_id'),
            vaultId: $request->input('vault_id'),
            confidenceScore: $request->input('confidence_score'),
            ipAddress: $request->ip(),
            metadata: $request->input('metadata'),
        );
    }

    public function toArray(): array
    {
        return [
            'device_id' => $this->deviceId,
            'fingerprint_id' => $this->fingerprintId,
            'user_id' => $this->userId,
            'vault_id' => $this->vaultId,
            'confidence_score' => $this->confidenceScore,
            'ip_address' => $this->ipAddress,
            'metadata' => $this->metadata,
        ];
    }
}
