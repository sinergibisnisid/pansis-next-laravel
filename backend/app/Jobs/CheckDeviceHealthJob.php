<?php

namespace App\Jobs;

use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\Device;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDeviceHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct()
    {
        $this->onQueue('monitoring');
    }

    public function handle(): void
    {
        $threshold = config('devices.heartbeat_timeout', 120);

        $devices = Device::where('status', '!=', DeviceStatus::Offline)
            ->where('is_active', true)
            ->where(function ($query) use ($threshold) {
                $query->whereNull('last_heartbeat_at')
                    ->orWhere('last_heartbeat_at', '<', now()->subSeconds($threshold));
            })
            ->get();

        $offlineCount = 0;

        foreach ($devices as $device) {
            $previousStatus = $device->status;

            $device->update([
                'status' => DeviceStatus::Offline,
            ]);

            event(new DeviceStatusChanged(
                deviceId: $device->id,
                previousStatus: $previousStatus->value,
                newStatus: DeviceStatus::Offline->value,
            ));

            $offlineCount++;
        }

        Log::info('Device health check completed', [
            'devices_marked_offline' => $offlineCount,
            'threshold_seconds' => $threshold,
        ]);
    }
}
