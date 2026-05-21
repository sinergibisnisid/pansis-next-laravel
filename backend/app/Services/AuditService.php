<?php

namespace App\Services;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public function log(
        User $user,
        AuditEvent $event,
        Model $auditable,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $user->id,
            'auditable_type' => get_class($auditable),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'metadata' => $metadata,
        ]);
    }

    public function getAuditTrail(string $auditableType, string $auditableId): Collection
    {
        return AuditLog::where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserActivity(string $userId, ?string $dateFrom = null, ?string $dateTo = null): Collection
    {
        $query = AuditLog::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->get();
    }
}
