<?php

namespace App\Listeners;

use App\Events\FingerprintScanned;
use App\Events\UnauthorizedAccessAttempt;
use App\Models\Vault;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleFingerprintScan implements ShouldQueue
{
    public function handle(FingerprintScanned $event): void
    {
        Log::info('Fingerprint scan processed', [
            'device_id' => $event->deviceId,
            'user_id' => $event->userId,
            'vault_id' => $event->vaultId,
            'scan_result' => $event->scanResult,
            'scanned_at' => $event->scannedAt->toIso8601String(),
        ]);

        // If scan failed, trigger unauthorized access event
        if ($event->scanResult === 'failed') {
            $vault = Vault::find($event->vaultId);
            $branchId = $vault?->branch_id ?? '';

            event(new UnauthorizedAccessAttempt(
                vaultId: $event->vaultId,
                branchId: $branchId,
                deviceId: $event->deviceId,
                userId: $event->userId,
                reason: 'Fingerprint scan failed',
                attemptedAt: $event->scannedAt,
            ));
        }
    }
}
