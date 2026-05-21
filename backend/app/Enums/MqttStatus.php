<?php

namespace App\Enums;

enum MqttStatus: string
{
    case Received = 'received';
    case Processed = 'processed';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Received',
            self::Processed => 'Processed',
            self::Failed => 'Failed',
        };
    }
}
