<?php

namespace App\Enums;

enum VaultStatus: string
{
    case Locked = 'locked';
    case Unlocked = 'unlocked';
    case Maintenance = 'maintenance';
    case Alarm = 'alarm';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Locked => 'Locked',
            self::Unlocked => 'Unlocked',
            self::Maintenance => 'Maintenance',
            self::Alarm => 'Alarm',
        };
    }
}
