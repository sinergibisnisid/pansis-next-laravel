<?php

namespace App\Enums;

/**
 * Magnetic lock relay state.
 * Per PDF: "Magnetic Lock 600 lbs / 12VDC. Saat akses valid → relay aktif → lock terbuka."
 */
enum LockState: string
{
    case Engaged = 'engaged';   // Lock is locked (relay off, magnet active)
    case Released = 'released'; // Lock is unlocked (relay on, magnet released)
    case Unknown = 'unknown';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Engaged => 'Terkunci',
            self::Released => 'Terbuka',
            self::Unknown => 'Status Tidak Diketahui',
        };
    }
}
