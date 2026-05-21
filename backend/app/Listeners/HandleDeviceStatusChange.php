<?php

namespace App\Listeners;

use App\Events\DeviceStatusChanged;
use App\Services\NotificationService;
use App\DTOs\Notification\SendNotificationDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HandleDeviceStatusChange implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(DeviceStatusChanged $event): void
    {
        Log::info('Device status changed', [
            'device_id' => $event->deviceId,
            'branch_id' => $event->branchId,
            'vault_id' => $event->vaultId,
            'status' => $event->status,
            'previous_status' => $event->previousStatus,
        ]);

        // If device went offline, send notification
        if ($event->status === 'offline' && $event->previousStatus !== 'offline') {
            $this->notificationService->send(new SendNotificationDTO(
                branchId: $event->branchId,
                channel: 'email',
                type: 'alert',
                title: "Device Offline Alert",
                body: "Device {$event->deviceId} in vault {$event->vaultId} has gone offline.",
            ));
        }

        // Update device cache
        Cache::put("device:{$event->deviceId}:status", [
            'status' => $event->status,
            'previous_status' => $event->previousStatus,
            'updated_at' => now()->toIso8601String(),
        ], now()->addHours(24));
    }
}
