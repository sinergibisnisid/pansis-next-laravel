<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface VaultRepositoryInterface extends BaseRepositoryInterface
{
    public function getByBranch(string $branchId): Collection;
    public function getActiveVaults(): Collection;
    public function updateStatus(string $vaultId, string $status): void;
    public function getWithActiveSessions(): Collection;
}
