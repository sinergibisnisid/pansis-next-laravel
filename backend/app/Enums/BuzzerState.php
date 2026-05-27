<?php

namespace App\Enums;

/**
 * Buzzer relay physical state.
 * Per PDF: "Alarm Buzzer 24VDC. Jika durasi melewati batas → controller aktifkan relay buzzer."
 */
enum BuzzerState: string
{
    case Off = 'off';
    case On = 'on';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Off => 'Mati',
            self::On => 'Bunyi',
        };
    }
}
