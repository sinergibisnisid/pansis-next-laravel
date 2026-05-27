<?php

namespace App\Enums;

/**
 * Direction of a controller I/O channel.
 * Per Pansin Access PDF: 4 input + 4 output channels.
 */
enum ChannelDirection: string
{
    case Input = 'input';
    case Output = 'output';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Input => 'Input',
            self::Output => 'Output',
        };
    }
}
