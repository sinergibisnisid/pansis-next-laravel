<?php

namespace App\Jobs;

use App\Services\HardwareCommandService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// Job retry command yang stuck (jalan tiap menit via scheduler)
class RetryStaleCommandsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function handle(HardwareCommandService $commandService): void
    {
        $retried = $commandService->retryStale(batchSize: 50);

        if ($retried > 0) {
            Log::info('Hardware command retry job completed', [
                'retried_count' => $retried,
            ]);
        }
    }
}