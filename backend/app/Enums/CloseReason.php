<?php

namespace App\Enums;

enum CloseReason: string
{
    case PushButton = 'push_button';     // Exit push button pressed inside vault
    case DoorClosed = 'door_closed';     // Door sensor detected closure
    case Manual = 'manual';              // Manually closed from dashboard
    case Timeout = 'timeout';            // Closed due to occupancy timeout
    case Emergency = 'emergency';        // Closed due to emergency button

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PushButton => 'Tombol Keluar',
            self::DoorClosed => 'Sensor Pintu',
            self::Manual => 'Manual',
            self::Timeout => 'Timeout',
            self::Emergency => 'Darurat',
        };
    }
}
