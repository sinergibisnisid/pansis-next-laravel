<?php

namespace App\Observers;

use App\Enums\AuditEvent;
use App\Enums\VaultStatus;
use App\Events\VaultAlarmTriggered;
use App\Models\AuditLog;
use App\Models\Vault;

class VaultObserver
{
    public function created(Vault $vault): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Vault::class,
            'auditable_id' => $vault->id,
            'event' => AuditEvent::Created,
            'old_values' => null,
            'new_values' => $vault->toArray(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function updated(Vault $vault): void
    {
        $original = $vault->getOriginal();
        $changes = $vault->getChanges();

        // If status changed, handle specific transitions
        if ($vault->wasChanged('status')) {
            $oldStatus = $original['status'] ?? null;
            $newStatus = $vault->status;

            // When status changes to alarm, dispatch VaultAlarmTriggered event
            if ($newStatus === VaultStatus::Alarm || (is_string($newStatus) && $newStatus === 'alarm')) {
                event(new VaultAlarmTriggered($vault));
            }
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Vault::class,
            'auditable_id' => $vault->id,
            'event' => AuditEvent::Updated,
            'old_values' => array_intersect_key($original, $changes),
            'new_values' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
