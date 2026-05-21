<?php

namespace App\Jobs;

use App\Events\SessionTimeoutWarning;
use App\Services\VaultService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckSessionTimeoutsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(VaultService $vaultService): void
    {
        $expiredSessions = $vaultService->checkSessionTimeout();

        foreach ($expiredSessions as $session) {
            event(new SessionTimeoutWarning(
                sessionId: $session->id,
                vaultId: $session->vault_id,
                userId: $session->user_id,
            ));
        }

        Log::info('Session timeout check completed', [
            'expired_sessions_count' => count($expiredSessions),
        ]);
    }
}
