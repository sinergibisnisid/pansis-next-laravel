<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface WorkingTimeRepositoryInterface extends BaseRepositoryInterface
{
    public function getByBranch(string $branchId): Collection;
    public function getByVault(string $vaultId): Collection;
    public function isWithinWorkingTime(string $branchId, ?string $vaultId = null): bool;
    public function getActiveSchedules(): Collection;
}
