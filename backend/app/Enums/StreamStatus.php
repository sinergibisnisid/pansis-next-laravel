<?php

namespace App\Enums;

enum StreamStatus: string
{
    case Active = 'active';
    case Stopped = 'stopped';
    case Error = 'error';
    case Buffering = 'buffering';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Stopped => 'Stopped',
            self::Error => 'Error',
            self::Buffering => 'Buffering',
        };
    }
}
