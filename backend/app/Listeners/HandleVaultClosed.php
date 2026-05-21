<?php

namespace App\Listeners;

use App\Events\VaultClosed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleVaultClosed implements ShouldQueue
{
    public function handle(VaultClosed $event): void
    {
        Log::info('Vault closed', [
            'vault_id' => $event->vaultId,
            'user_id' => $event->userId,
            'session_id' => $event->sessionId,
            'branch_id' => $event->branchId,
            'closed_at' => $event->closedAt->toIso8601String(),
            'duration_seconds' => $event->durationSeconds,
            'close_reason' => $event->closeReason,
        ]);

        Cache::put("vault:{$event->vaultId}:status", [
            'status' => 'locked',
            'last_session_id' => $event->sessionId,
            'closed_at' => $event->closedAt->toIso8601String(),
        ], now()->addHours(24));

        // Clear session timer
        Cache::forget("vault:{$event->vaultId}:session_timer");
    }
}
