<?php

namespace App\Actions\Vault;

use App\DTOs\Vault\CloseVaultDTO;
use App\Services\AuditService;
use App\Services\VaultService;

class CloseVaultAction
{
    public function __construct(
        private readonly VaultService $vaultService,
        private readonly AuditService $auditService,
    ) {}

    public function execute(CloseVaultDTO $dto): array
    {
        return $this->vaultService->closeVault($dto);
    }
}
