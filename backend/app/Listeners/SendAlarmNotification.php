<?php

namespace App\Listeners;

use App\Events\VaultAlarmTriggered;
use App\Models\AlarmLog;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAlarmNotification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function handle(VaultAlarmTriggered $event): void
    {
        $alarmLog = AlarmLog::find($event->alarmLogId);

        if ($alarmLog) {
            $this->notificationService->sendAlarmNotification($alarmLog);
        }
    }
}
