<?php

namespace App\Enums;

enum AlarmStatus: string
{
    case Active = 'active';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case FalseAlarm = 'false_alarm';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Acknowledged => 'Acknowledged',
            self::Resolved => 'Resolved',
            self::FalseAlarm => 'False Alarm',
        };
    }
}
