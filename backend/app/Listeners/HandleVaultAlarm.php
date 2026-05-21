<?php

namespace App\Listeners;

use App\Events\VaultAlarmTriggered;
use App\Services\MqttService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleVaultAlarm implements ShouldQueue
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly MqttService $mqttService,
    ) {}

    public function handle(VaultAlarmTriggered $event): void
    {
        Log::critical('Vault alarm triggered', [
            'vault_id' => $event->vaultId,
            'branch_id' => $event->branchId,
            'alarm_type' => $event->alarmType,
            'severity' => $event->severity,
            'alarm_log_id' => $event->alarmLogId,
            'triggered_at' => $event->triggeredAt->toIso8601String(),
        ]);

        // Send notifications to configured recipients
        $recipients = $this->notificationService->getConfiguredRecipients('alarm', $event->branchId);

        $title = "ALARM: {$event->alarmType} - Vault {$event->vaultId}";
        $body = "An alarm has been triggered.\n\n"
            . "Type: {$event->alarmType}\n"
            . "Vault: {$event->vaultId}\n"
            . "Severity: {$event->severity}\n"
            . "Time: {$event->triggeredAt->toDateTimeString()}";

        foreach ($recipients as $recipient) {
            $this->notificationService->sendWhatsApp($recipient, $title, $body);
            $this->notificationService->sendEmail($recipient, $title, $body);
        }

        // Trigger buzzer via MQTT
        $this->mqttService->publish("vault/{$event->vaultId}/alarm", [
            'action' => 'trigger_buzzer',
            'alarm_type' => $event->alarmType,
            'severity' => $event->severity,
            'triggered_at' => $event->triggeredAt->toIso8601String(),
        ]);
    }
}
