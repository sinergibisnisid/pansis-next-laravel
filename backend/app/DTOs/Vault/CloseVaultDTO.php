<?php

namespace App\DTOs\Vault;

use Illuminate\Http\Request;

readonly class CloseVaultDTO
{
    public function __construct(
        public string $vaultId,
        public string $sessionId,
        public string $userId,
        public ?string $closeReason = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            vaultId: $request->input('vault_id'),
            sessionId: $request->input('session_id'),
            userId: $request->input('user_id'),
            closeReason: $request->input('close_reason'),
        );
    }

    public function toArray(): array
    {
        return [
            'vault_id' => $this->vaultId,
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'close_reason' => $this->closeReason,
        ];
    }
}
