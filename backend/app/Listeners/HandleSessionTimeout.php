<?php

namespace App\Listeners;

use App\Events\SessionTimeoutWarning;
use App\DTOs\Notification\SendNotificationDTO;
use App\Services\MqttService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleSessionTimeout implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly MqttService $mqttService,
    ) {}

    public function handle(SessionTimeoutWarning $event): void
    {
        Log::warning('Session timeout warning', [
            'session_id' => $event->sessionId,
            'vault_id' => $event->vaultId,
            'branch_id' => $event->branchId,
            'elapsed_seconds' => $event->elapsedSeconds,
            'max_seconds' => $event->maxSeconds,
        ]);

        $remainingSeconds = $event->maxSeconds - $event->elapsedSeconds;

        // Send notification
        $this->notificationService->send(new SendNotificationDTO(
            branchId: $event->branchId,
            channel: 'whatsapp',
            type: 'alert',
            title: 'Session Timeout Warning',
            body: "Vault {$event->vaultId} session has been open for {$event->elapsedSeconds} seconds. "
                . "Remaining time: {$remainingSeconds} seconds. Please close the vault.",
        ));

        // Trigger buzzer via MQTT
        $this->mqttService->publish("vault/{$event->vaultId}/buzzer", [
            'action' => 'timeout_warning',
            'session_id' => $event->sessionId,
            'elapsed_seconds' => $event->elapsedSeconds,
            'remaining_seconds' => $remainingSeconds,
        ]);
    }
}
