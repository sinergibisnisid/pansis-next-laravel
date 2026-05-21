<?php

namespace App\Enums;

enum DeviceType: string
{
    case Controller = 'controller';
    case FingerprintScanner = 'fingerprint_scanner';
    case Camera = 'camera';
    case Sensor = 'sensor';
    case Buzzer = 'buzzer';
    case Lock = 'lock';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Controller => 'Controller',
            self::FingerprintScanner => 'Fingerprint Scanner',
            self::Camera => 'Camera',
            self::Sensor => 'Sensor',
            self::Buzzer => 'Buzzer',
            self::Lock => 'Lock',
        };
    }
}
