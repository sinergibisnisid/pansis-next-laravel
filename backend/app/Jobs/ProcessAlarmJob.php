<?php

namespace App\Jobs;

use App\Events\VaultAlarmTriggered;
use App\Models\AlarmLog;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAlarmJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(
        public readonly string $alarmLogId,
    ) {
        $this->onQueue('alarms');
    }

    public function uniqueId(): string
    {
        return $this->alarmLogId;
    }

    public function handle(NotificationService $notificationService): void
    {
        $alarmLog = AlarmLog::with(['vault', 'device', 'branch'])->findOrFail($this->alarmLogId);

        event(new VaultAlarmTriggered(
            alarmLogId: $alarmLog->id,
            vaultId: $alarmLog->vault_id,
            alarmType: $alarmLog->alarm_type->value,
            severity: $alarmLog->severity->value,
        ));

        $notificationService->sendAlarmNotification($alarmLog);

        Log::warning('Alarm processed', [
            'alarm_log_id' => $this->alarmLogId,
            'alarm_type' => $alarmLog->alarm_type->value,
            'severity' => $alarmLog->severity->value,
            'vault_id' => $alarmLog->vault_id,
        ]);
    }
}
