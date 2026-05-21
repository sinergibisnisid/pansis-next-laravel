<?php

namespace App\Enums;

enum AlarmType: string
{
    case UnauthorizedAccess = 'unauthorized_access';
    case SessionTimeout = 'session_timeout';
    case DeviceTamper = 'device_tamper';
    case Emergency = 'emergency';
    case SensorTrigger = 'sensor_trigger';
    case Manual = 'manual';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::UnauthorizedAccess => 'Unauthorized Access',
            self::SessionTimeout => 'Session Timeout',
            self::DeviceTamper => 'Device Tamper',
            self::Emergency => 'Emergency',
            self::SensorTrigger => 'Sensor Trigger',
            self::Manual => 'Manual',
        };
    }
}
