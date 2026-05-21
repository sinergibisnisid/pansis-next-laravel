<?php

namespace App\Listeners;

use App\Events\VaultOpened;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleVaultOpened implements ShouldQueue
{
    public function handle(VaultOpened $event): void
    {
        Log::info('Vault opened', [
            'vault_id' => $event->vaultId,
            'user_id' => $event->userId,
            'session_id' => $event->sessionId,
            'branch_id' => $event->branchId,
            'opened_at' => $event->openedAt->toIso8601String(),
        ]);

        Cache::put("vault:{$event->vaultId}:status", [
            'status' => 'unlocked',
            'session_id' => $event->sessionId,
            'user_id' => $event->userId,
            'opened_at' => $event->openedAt->toIso8601String(),
        ], now()->addHours(24));
    }
}
