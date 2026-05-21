<?php

namespace App\Repositories;

use App\Models\VaultSession;
use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class VaultSessionRepository extends BaseRepository implements VaultSessionRepositoryInterface
{
    public function __construct(VaultSession $model)
    {
        parent::__construct($model);
    }

    public function getActiveSessions(): Collection
    {
        return $this->model->newQuery()->whereNull('closed_at')->get();
    }

    public function getActiveSessionByVault(string $vaultId): ?Model
    {
        return $this->model->newQuery()
            ->where('vault_id', $vaultId)
            ->whereNull('closed_at')
            ->first();
    }

    public function closeSession(string $sessionId, array $data = []): void
    {
        $this->model->newQuery()
            ->where('id', $sessionId)
            ->update(array_merge($data, ['closed_at' => now()]));
    }

    public function getExpiredSessions(): Collection
    {
        return $this->model->newQuery()
            ->whereNull('closed_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }

    public function getSessionsByUser(string $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }
}
