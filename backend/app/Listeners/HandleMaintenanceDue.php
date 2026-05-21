<?php

namespace App\Listeners;

use App\Events\MaintenanceDue;
use App\DTOs\Notification\SendNotificationDTO;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleMaintenanceDue implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(MaintenanceDue $event): void
    {
        Log::info('Maintenance due notification', [
            'plan_id' => $event->planId,
            'branch_id' => $event->branchId,
            'vault_id' => $event->vaultId,
            'device_id' => $event->deviceId,
            'scheduled_date' => $event->scheduledDate,
            'type' => $event->type,
        ]);

        $this->notificationService->send(new SendNotificationDTO(
            branchId: $event->branchId,
            channel: 'email',
            type: 'maintenance',
            title: "Maintenance Due: {$event->type}",
            body: "A maintenance task is due.\n\n"
                . "Plan ID: {$event->planId}\n"
                . "Type: {$event->type}\n"
                . "Vault: {$event->vaultId}\n"
                . "Device: {$event->deviceId}\n"
                . "Scheduled Date: {$event->scheduledDate}",
        ));
    }
}
