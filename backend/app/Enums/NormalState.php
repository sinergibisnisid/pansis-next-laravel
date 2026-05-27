<?php

namespace App\Enums;

/**
 * Electrical normal state of a circuit attached to a controller channel.
 * Per Pansin Access PDF:
 *   - Emergency button: Normally Closed (NC) — pressing breaks the circuit.
 *   - Exit push button: Normally Open (NO)   — pressing closes the circuit.
 */
enum NormalState: string
{
    case NormallyOpen = 'normally_open';
    case NormallyClosed = 'normally_closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::NormallyOpen => 'Normally Open (NO)',
            self::NormallyClosed => 'Normally Closed (NC)',
        };
    }
}
