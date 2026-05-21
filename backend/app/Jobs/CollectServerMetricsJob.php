<?php

namespace App\Jobs;

use App\Services\ServerMonitoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectServerMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(ServerMonitoringService $serverMonitoringService): void
    {
        $metrics = $serverMonitoringService->collectMetrics();
        $serverMonitoringService->saveMetrics($metrics);

        Log::info('Server metrics collected successfully');
    }
}
