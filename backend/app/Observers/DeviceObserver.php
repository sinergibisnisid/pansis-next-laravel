<?php

namespace App\Observers;

use App\Enums\AuditEvent;
use App\Enums\DeviceStatus;
use App\Events\DeviceStatusChanged;
use App\Models\AuditLog;
use App\Models\Device;

class DeviceObserver
{
    public function created(Device $device): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Device::class,
            'auditable_id' => $device->id,
            'event' => AuditEvent::Created,
            'old_values' => null,
            'new_values' => $device->toArray(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function updated(Device $device): void
    {
        $original = $device->getOriginal();
        $changes = $device->getChanges();

        // If status changed between online and offline, dispatch event
        if ($device->wasChanged('status')) {
            $oldStatus = $original['status'] ?? null;
            $newStatus = $device->status;

            $onlineOfflineStatuses = [DeviceStatus::Online, DeviceStatus::Offline];

            $oldIsOnlineOffline = in_array($oldStatus, $onlineOfflineStatuses) ||
                in_array($oldStatus, ['online', 'offline']);
            $newIsOnlineOffline = in_array($newStatus, $onlineOfflineStatuses);

            if ($oldIsOnlineOffline || $newIsOnlineOffline) {
                event(new DeviceStatusChanged($device, $oldStatus, $newStatus));
            }
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Device::class,
            'auditable_id' => $device->id,
            'event' => AuditEvent::Updated,
            'old_values' => array_intersect_key($original, $changes),
            'new_values' => $changes,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public function deleted(Device $device): void
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => Device::class,
            'auditable_id' => $device->id,
            'event' => AuditEvent::Deleted,
            'old_values' => $device->toArray(),
            'new_values' => null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
