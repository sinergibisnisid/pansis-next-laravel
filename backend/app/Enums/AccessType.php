<?php

namespace App\Enums;

enum AccessType: string
{
    case Fingerprint = 'fingerprint';
    case ManualOverride = 'manual_override';
    case Emergency = 'emergency';
    case Maintenance = 'maintenance';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Fingerprint => 'Fingerprint',
            self::ManualOverride => 'Manual Override',
            self::Emergency => 'Emergency',
            self::Maintenance => 'Maintenance',
        };
    }
}
