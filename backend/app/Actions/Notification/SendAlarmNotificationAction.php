<?php

namespace App\Actions\Notification;

use App\Models\AlarmLog;
use App\Services\NotificationService;

class SendAlarmNotificationAction
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(AlarmLog $alarm): void
    {
        $this->notificationService->sendAlarmNotification($alarm);
    }
}
