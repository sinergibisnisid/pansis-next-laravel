<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface VaultSessionRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveSessions(): Collection;
    public function getActiveSessionByVault(string $vaultId): ?Model;
    public function closeSession(string $sessionId, array $data = []): void;
    public function getExpiredSessions(): Collection;
    public function getSessionsByUser(string $userId): Collection;
}
