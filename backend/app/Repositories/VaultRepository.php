<?php

namespace App\Repositories;

use App\Models\Vault;
use App\Repositories\Contracts\VaultRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class VaultRepository extends BaseRepository implements VaultRepositoryInterface
{
    public function __construct(Vault $model)
    {
        parent::__construct($model);
    }

    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()->where('branch_id', $branchId)->get();
    }

    public function getActiveVaults(): Collection
    {
        return $this->model->newQuery()->where('status', 'active')->get();
    }

    public function updateStatus(string $vaultId, string $status): void
    {
        $this->model->newQuery()
            ->where('id', $vaultId)
            ->update(['status' => $status]);
    }

    public function getWithActiveSessions(): Collection
    {
        return $this->model->newQuery()
            ->whereHas('sessions', function ($query) {
                $query->whereNull('closed_at');
            })
            ->with(['sessions' => function ($query) {
                $query->whereNull('closed_at');
            }])
            ->get();
    }
}
