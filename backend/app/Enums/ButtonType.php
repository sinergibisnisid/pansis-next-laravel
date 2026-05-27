<?php

namespace App\Enums;

/**
 * Physical button input on the controller.
 * Per PDF: vault has Emergency Button (Normally Closed) and Exit Push Button (Normally Open).
 */
enum ButtonType: string
{
    case Exit = 'exit';
    case Emergency = 'emergency';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Exit => 'Tombol Keluar',
            self::Emergency => 'Tombol Darurat',
        };
    }
}
