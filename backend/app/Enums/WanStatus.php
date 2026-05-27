<?php

namespace App\Enums;

/**
 * WAN / internet uplink status reported by the branch router/controller.
 * Per Pansin Access PDF "Network Failover" feature.
 */
enum WanStatus: string
{
    case Online = 'online';
    case Offline = 'offline';
    case Degraded = 'degraded';
    case Failover = 'failover';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Online',
            self::Offline => 'Offline',
            self::Degraded => 'Degraded',
            self::Failover => 'Failover (Backup Link)',
        };
    }
}
