<?php

namespace App\Enums;

enum AccessStatus: string
{
    case Granted = 'granted';
    case Denied = 'denied';
    case AlarmTriggered = 'alarm_triggered';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Granted => 'Granted',
            self::Denied => 'Denied',
            self::AlarmTriggered => 'Alarm Triggered',
        };
    }
}
