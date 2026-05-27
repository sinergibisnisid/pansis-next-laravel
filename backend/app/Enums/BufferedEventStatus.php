<?php

namespace App\Enums;

/**
 * Processing state of a buffered offline event.
 */
enum BufferedEventStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';
    case Skipped = 'skipped';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Processing => 'Diproses',
            self::Processed => 'Selesai',
            self::Failed => 'Gagal',
            self::Skipped => 'Dilewati',
        };
    }
}
