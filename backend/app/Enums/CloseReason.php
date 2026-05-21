<?php

namespace App\Enums;

enum CloseReason: string
{
    case PushButton = 'push_button';
    case Manual = 'manual';
    case Timeout = 'timeout';
    case Emergency = 'emergency';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::PushButton => 'Push Button',
            self::Manual => 'Manual',
            self::Timeout => 'Timeout',
            self::Emergency => 'Emergency',
        };
    }
}
