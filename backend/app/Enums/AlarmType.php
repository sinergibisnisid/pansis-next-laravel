<?php

namespace App\Enums;

enum AlarmType: string
{
    case UnauthorizedAccess = 'unauthorized_access';
    case SessionTimeout = 'session_timeout';
    case DeviceTamper = 'device_tamper';
    case Emergency = 'emergency';            // Triggered by emergency button press
    case SensorTrigger = 'sensor_trigger';
    case DoorForcedOpen = 'door_forced_open'; // Door opened without valid access
    case DoorLeftOpen = 'door_left_open';     // Door open beyond expected duration
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
            self::Emergency => 'Emergency Button',
            self::SensorTrigger => 'Sensor Trigger',
            self::DoorForcedOpen => 'Door Forced Open',
            self::DoorLeftOpen => 'Door Left Open',
            self::Manual => 'Manual',
        };
    }
}
