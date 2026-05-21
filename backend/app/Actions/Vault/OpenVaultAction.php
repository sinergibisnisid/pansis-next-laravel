<?php

namespace App\Actions\Vault;

use App\DTOs\Vault\VaultAccessDTO;
use App\Services\AuditService;
use App\Services\SnapshotService;
use App\Services\VaultService;
use Illuminate\Validation\ValidationException;

class OpenVaultAction
{
    public function __construct(
        private readonly VaultService $vaultService,
        private readonly AuditService $auditService,
        private readonly SnapshotService $snapshotService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function execute(VaultAccessDTO $dto): array
    {
        return $this->vaultService->openVault($dto);
    }
}
