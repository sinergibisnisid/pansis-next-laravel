<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BroadcastVaultStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 15;

    public function __construct(
        public readonly string $vaultId,
        public readonly string $status,
        public readonly array $metadata = [],
    ) {
        $this->onQueue('broadcasting');
    }

    public function handle(): void
    {
        broadcast(new \App\Events\VaultStatusUpdated(
            vaultId: $this->vaultId,
            status: $this->status,
            metadata: $this->metadata,
        ))->toOthers();

        Log::info('Vault status broadcasted', [
            'vault_id' => $this->vaultId,
            'status' => $this->status,
        ]);
    }
}
