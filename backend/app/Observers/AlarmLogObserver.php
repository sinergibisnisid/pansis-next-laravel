<?php

namespace App\Observers;

use App\Enums\AuditEvent;
use App\Jobs\ProcessAlarmJob;
use App\Models\AlarmLog;
use App\Models\AuditLog;

class AlarmLogObserver
{
    public function created(AlarmLog $alarmLog): void
    {
        // Dispatch ProcessAlarmJob when a new alarm is created
        ProcessAlarmJob::dispatch($alarmLog);
    }

    public function updated(AlarmLog $alarmLog): void
    {
        // If status changed to resolved, log audit
        if ($alarmLog->wasChanged('status') && $alarmLog->status->value === 'resolved') {
            AuditLog::create([
                'user_id' => auth()->id(),
                'auditable_type' => AlarmLog::class,
                'auditable_id' => $alarmLog->id,
                'event' => AuditEvent::AlarmResolved,
                'old_values' => ['status' => $alarmLog->getOriginal('status')],
                'new_values' => [
                    'status' => $alarmLog->status->value,
                    'resolved_by' => $alarmLog->resolved_by,
                    'resolved_at' => $alarmLog->resolved_at?->toISOString(),
                    'resolution_notes' => $alarmLog->resolution_notes,
                ],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        }
    }
}
