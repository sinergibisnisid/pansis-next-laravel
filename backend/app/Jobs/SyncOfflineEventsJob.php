<?php

namespace App\Jobs;

use App\Services\OfflineSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Periodically drains the device_event_buffers queue.
 *
 * Scheduled to run every minute (see App\Console\Kernel). Also dispatched
 * inline by SyncBatchController right after a controller uploads a batch,
 * so the events are replayed without waiting for the cron tick.
 */
class SyncOfflineEventsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public string $queue = 'monitoring';

    public function __construct(
        public readonly int $batchSize = 200,
    ) {}

    public function handle(OfflineSyncService $syncService): void
    {
        $syncService->processPending($this->batchSize);
    }
}
