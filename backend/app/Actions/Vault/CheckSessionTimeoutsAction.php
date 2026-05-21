<?php

namespace App\Actions\Vault;

use App\Repositories\Contracts\VaultSessionRepositoryInterface;
use App\Services\VaultService;

class CheckSessionTimeoutsAction
{
    public function __construct(
        private readonly VaultService $vaultService,
        private readonly VaultSessionRepositoryInterface $vaultSessionRepository,
    ) {}

    public function execute(): int
    {
        $expiredSessions = $this->vaultSessionRepository->getExpiredSessions();
        $count = $expiredSessions->count();

        if ($count > 0) {
            $this->vaultService->checkSessionTimeout();
        }

        return $count;
    }
}
