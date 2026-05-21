<?php

namespace App\Enums;

enum StreamQuality: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Hd = 'hd';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Hd => 'HD',
        };
    }
}
