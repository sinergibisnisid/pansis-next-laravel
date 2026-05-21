<?php

namespace App\Listeners;

use App\Events\DeviceHeartbeatReceived;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleDeviceHeartbeat implements ShouldQueue
{
    public function __construct(
        private readonly DeviceRepositoryInterface $deviceRepository,
    ) {}

    public function handle(DeviceHeartbeatReceived $event): void
    {
        Log::debug('Device heartbeat received', [
            'device_id' => $event->deviceId,
            'status' => $event->status,
            'recorded_at' => $event->recordedAt->toIso8601String(),
        ]);

        $this->deviceRepository->updateHeartbeat($event->deviceId, [
            'last_heartbeat_at' => $event->recordedAt,
            'status' => $event->status,
        ]);
    }
}
