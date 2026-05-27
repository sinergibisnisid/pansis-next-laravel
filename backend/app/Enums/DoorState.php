<?php

namespace App\Enums;

enum DoorState: string
{
    case Closed = 'closed';
    case Opened = 'opened';
    case Unknown = 'unknown';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Closed => 'Pintu Tertutup',
            self::Opened => 'Pintu Terbuka',
            self::Unknown => 'Status Tidak Diketahui',
        };
    }
}
