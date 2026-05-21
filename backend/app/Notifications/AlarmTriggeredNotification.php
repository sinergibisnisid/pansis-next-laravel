<?php

namespace App\Notifications;

use App\Models\AlarmLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlarmTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly AlarmLog $alarmLog
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $alarm = $this->alarmLog;
        $severityLabel = strtoupper($alarm->severity->value);

        return (new MailMessage)
            ->subject("[ALARM {$severityLabel}] {$alarm->title}")
            ->greeting("Alarm Triggered - {$severityLabel}")
            ->line("An alarm has been triggered in the system.")
            ->line("**Type:** {$alarm->alarm_type->value}")
            ->line("**Severity:** {$severityLabel}")
            ->line("**Branch:** {$alarm->branch?->name}")
            ->line("**Vault:** {$alarm->vault?->name} ({$alarm->vault?->code})")
            ->line("**Time:** {$alarm->triggered_at->format('d M Y H:i:s')}")
            ->line("**Description:** {$alarm->description}")
            ->action('View Alarm Details', url("/alarms/{$alarm->id}"))
            ->line('Please acknowledge and resolve this alarm as soon as possible.')
            ->salutation('PANSIN ACCESS Monitoring System');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alarm_log_id' => $this->alarmLog->id,
            'alarm_type' => $this->alarmLog->alarm_type->value,
            'severity' => $this->alarmLog->severity->value,
            'status' => $this->alarmLog->status->value,
            'title' => $this->alarmLog->title,
            'description' => $this->alarmLog->description,
            'vault_id' => $this->alarmLog->vault_id,
            'branch_id' => $this->alarmLog->branch_id,
            'device_id' => $this->alarmLog->device_id,
            'triggered_at' => $this->alarmLog->triggered_at?->toISOString(),
        ];
    }
}
