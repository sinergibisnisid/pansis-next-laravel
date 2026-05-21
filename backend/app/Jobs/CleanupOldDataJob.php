<?php

namespace App\Jobs;

use App\Models\DeviceHeartbeat;
use App\Models\MqttLog;
use App\Models\ServerMonitoring;
use App\Models\Snapshot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $heartbeatsDeleted = DeviceHeartbeat::where('created_at', '<', now()->subDays(30))->delete();

        $mqttLogsDeleted = MqttLog::where('created_at', '<', now()->subDays(30))->delete();

        $snapshotsDeleted = Snapshot::where('created_at', '<', now()->subDays(90))->delete();

        $serverMonitoringsDeleted = ServerMonitoring::where('created_at', '<', now()->subDays(7))->delete();

        Log::info('Old data cleanup completed', [
            'heartbeats_deleted' => $heartbeatsDeleted,
            'mqtt_logs_deleted' => $mqttLogsDeleted,
            'snapshots_deleted' => $snapshotsDeleted,
            'server_monitorings_deleted' => $serverMonitoringsDeleted,
        ]);
    }
}
