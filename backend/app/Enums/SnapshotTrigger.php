<?php

namespace App\Enums;

enum SnapshotTrigger: string
{
    case VaultOpen = 'vault_open';
    case VaultClose = 'vault_close';
    case Alarm = 'alarm';
    case Manual = 'manual';
    case Scheduled = 'scheduled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::VaultOpen => 'Vault Open',
            self::VaultClose => 'Vault Close',
            self::Alarm => 'Alarm',
            self::Manual => 'Manual',
            self::Scheduled => 'Scheduled',
        };
    }
}
