<?php

namespace App\Enums;

enum NotificationType: string
{
    case Alarm = 'alarm';
    case Access = 'access';
    case Maintenance = 'maintenance';
    case Report = 'report';
    case System = 'system';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Alarm => 'Alarm',
            self::Access => 'Access',
            self::Maintenance => 'Maintenance',
            self::Report => 'Report',
            self::System => 'System',
        };
    }
}
