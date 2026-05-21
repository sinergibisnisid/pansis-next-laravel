<?php

namespace App\Enums;

enum HeartbeatStatus: string
{
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Critical = 'critical';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::Degraded => 'Degraded',
            self::Critical => 'Critical',
        };
    }
}
