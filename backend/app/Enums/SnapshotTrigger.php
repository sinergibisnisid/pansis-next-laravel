<?php

namespace App\Enums;

/**
 * What event triggered the camera snapshot.
 * Per PDF: snapshot is captured when door physically opens (DoorOpen),
 * when alarm fires, or manually from dashboard.
 */
enum SnapshotTrigger: string
{
    case DoorOpen = 'door_open';     // Triggered by door sensor opened (PDF: PHASE 2 step 7)
    case DoorClose = 'door_close';   // Triggered by door sensor closed
    case VaultOpen = 'vault_open';   // Triggered by vault session created (kept for backward compat)
    case VaultClose = 'vault_close'; // Triggered by vault session closed
    case Alarm = 'alarm';            // Triggered by alarm event
    case Emergency = 'emergency';    // Triggered by emergency button
    case Manual = 'manual';          // Manual trigger from dashboard
    case Scheduled = 'scheduled';    // Scheduled periodic snapshot

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::DoorOpen => 'Pintu Terbuka',
            self::DoorClose => 'Pintu Tertutup',
            self::VaultOpen => 'Vault Open',
            self::VaultClose => 'Vault Close',
            self::Alarm => 'Alarm',
            self::Emergency => 'Tombol Darurat',
            self::Manual => 'Manual',
            self::Scheduled => 'Terjadwal',
        };
    }
}
