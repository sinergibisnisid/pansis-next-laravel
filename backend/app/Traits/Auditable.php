<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Enums\AuditEvent;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->logAudit(AuditEvent::Created, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();
            if (!empty($dirty)) {
                $original = array_intersect_key($model->getOriginal(), $dirty);
                $model->logAudit(AuditEvent::Updated, $original, $dirty);
            }
        });

        static::deleted(function ($model) {
            $model->logAudit(AuditEvent::Deleted, $model->getOriginal(), null);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->logAudit(AuditEvent::Restored, null, $model->getAttributes());
            });
        }
    }

    protected function logAudit(AuditEvent $event, ?array $oldValues, ?array $newValues): void
    {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
        ]);
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
