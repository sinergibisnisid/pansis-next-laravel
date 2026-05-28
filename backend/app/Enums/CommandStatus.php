<?php

namespace App\Enums;

/**
 * Lifecycle of a hardware command (lock release/engage, buzzer on/off).
 */
enum CommandStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Acknowledged = 'acknowledged';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Kirim',
            self::Sent => 'Terkirim',
            self::Acknowledged => 'Diterima',
            self::Failed => 'Gagal',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Acknowledged, self::Failed, self::Cancelled], true);
    }
}
