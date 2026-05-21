<?php

namespace App\Enums;

enum ScanResult: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Rejected = 'rejected';
    case Timeout = 'timeout';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Success => 'Success',
            self::Failed => 'Failed',
            self::Rejected => 'Rejected',
            self::Timeout => 'Timeout',
        };
    }
}
