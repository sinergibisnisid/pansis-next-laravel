<?php

namespace App\Enums;

enum MaintenanceType: string
{
    case Cleaning = 'cleaning';
    case Lubrication = 'lubrication';
    case Inspection = 'inspection';
    case Repair = 'repair';
    case Calibration = 'calibration';
    case Replacement = 'replacement';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Cleaning => 'Cleaning',
            self::Lubrication => 'Lubrication',
            self::Inspection => 'Inspection',
            self::Repair => 'Repair',
            self::Calibration => 'Calibration',
            self::Replacement => 'Replacement',
        };
    }
}
