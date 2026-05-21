<?php

namespace App\Listeners;

use App\Events\UnauthorizedAccessAttempt;
use App\Models\AlarmLog;
use App\Services\MqttService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleUnauthorizedAccess implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly MqttService $mqttService,
    ) {}

    public function handle(UnauthorizedAccessAttempt $event): void
    {
        Log::critical('Unauthorized access attempt', [
            'vault_id' => $event->vaultId,
            'branch_id' => $event->branchId,
            'device_id' => $event->deviceId,
            'user_id' => $event->userId,
            'reason' => $event->reason,
            'attempted_at' => $event->attemptedAt->toIso8601String(),
        ]);

        // Create alarm log
        $alarmLog = AlarmLog::create([
            'vault_id' => $event->vaultId,
            'alarm_type' => 'unauthorized_access',
            'severity' => 'critical',
            'description' => "Unauthorized access attempt: {$event->reason}",
            'triggered_by' => $event->userId,
            'device_id' => $event->deviceId,
            'metadata' => [
                'reason' => $event->reason,
                'attempted_at' => $event->attemptedAt->toIso8601String(),
            ],
        ]);

        // Send notifications
        $this->notificationService->sendUnauthorizedAccessAlert(
            $event->vaultId,
            $event->userId,
            $event->reason,
        );

        // Trigger alarm via MQTT
        $this->mqttService->publish("vault/{$event->vaultId}/alarm", [
            'action' => 'trigger_alarm',
            'alarm_type' => 'unauthorized_access',
            'severity' => 'critical',
            'reason' => $event->reason,
            'user_id' => $event->userId,
            'attempted_at' => $event->attemptedAt->toIso8601String(),
        ]);
    }
}
