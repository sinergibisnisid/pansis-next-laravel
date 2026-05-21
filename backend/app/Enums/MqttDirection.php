<?php

namespace App\Enums;

enum MqttDirection: string
{
    case Incoming = 'incoming';
    case Outgoing = 'outgoing';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Incoming => 'Incoming',
            self::Outgoing => 'Outgoing',
        };
    }
}
