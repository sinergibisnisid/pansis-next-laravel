<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class DailyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Carbon $reportDate,
        public readonly array $stats
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->reportDate->format('d M Y');
        $reportUrl = url('/reports/daily/' . $this->reportDate->format('Y-m-d'));

        $totalSessions = $this->stats['total_sessions'] ?? 0;
        $successfulAccess = $this->stats['successful_access'] ?? 0;
        $failedAccess = $this->stats['failed_access'] ?? 0;
        $alarmsTriggered = $this->stats['alarms_triggered'] ?? 0;
        $alarmsResolved = $this->stats['alarms_resolved'] ?? 0;
        $alarmsPending = $this->stats['alarms_pending'] ?? 0;
        $devicesOnline = $this->stats['devices_online'] ?? 0;
        $devicesOffline = $this->stats['devices_offline'] ?? 0;
        $sessionTimeouts = $this->stats['session_timeouts'] ?? 0;
        $maintenanceCompleted = $this->stats['maintenance_completed'] ?? 0;
        $maintenancePending = $this->stats['maintenance_pending'] ?? 0;

        $message = (new MailMessage)
            ->subject("[DAILY REPORT] PANSIN ACCESS - {$date}")
            ->greeting("Daily Report - {$date}")
            ->line('Here is your daily summary report for PANSIN ACCESS monitoring system.')
            ->line('---')
            ->line("**Total Vault Sessions:** {$totalSessions}")
            ->line("**Successful Access:** {$successfulAccess}")
            ->line("**Failed Access Attempts:** {$failedAccess}")
            ->line("**Alarms Triggered:** {$alarmsTriggered}")
            ->line("**Alarms Resolved:** {$alarmsResolved}")
            ->line("**Alarms Pending:** {$alarmsPending}")
            ->line("**Devices Online:** {$devicesOnline}")
            ->line("**Devices Offline:** {$devicesOffline}")
            ->line("**Session Timeouts:** {$sessionTimeouts}")
            ->line("**Maintenance Completed:** {$maintenanceCompleted}")
            ->line("**Maintenance Pending:** {$maintenancePending}")
            ->line('---')
            ->action('View Full Report', $reportUrl)
            ->line('This is an automated daily report from PANSIN ACCESS.')
            ->salutation('PANSIN ACCESS Monitoring System');

        return $message;
    }
}
