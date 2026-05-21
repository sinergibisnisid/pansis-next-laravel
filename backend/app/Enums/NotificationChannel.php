<?php

namespace App\Enums;

enum NotificationChannel: string
{
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Broadcast = 'broadcast';
    case Push = 'push';
    case Both = 'both';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Email => 'Email',
            self::Broadcast => 'Broadcast',
            self::Push => 'Push',
            self::Both => 'Both',
        };
    }
}
