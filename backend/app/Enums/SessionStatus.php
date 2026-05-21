<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Timeout = 'timeout';
    case Alarm = 'alarm';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Closed => 'Closed',
            self::Timeout => 'Timeout',
            self::Alarm => 'Alarm',
        };
    }
}
