<?php

namespace App\Enums;

enum WorkingTimeType: string
{
    case Recurring = 'recurring';
    case SpecificDate = 'specific_date';
    case Holiday = 'holiday';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Recurring => 'Recurring',
            self::SpecificDate => 'Specific Date',
            self::Holiday => 'Holiday',
        };
    }
}
