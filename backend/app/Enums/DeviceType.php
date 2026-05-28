<?php

namespace App\Enums;

/**
 * Physical device types in the Pansin Access platform.
 *
 * Per Pansin Access PDF "Komponen / Alat":
 *   - Controller (Intelligence Controller — 4 in / 4 out)
 *   - Fingerprint Scanner (TCP/IP biometric)
 *   - IP Camera (RTSP / ONVIF)
 *   - Door Sensor / Buzzer / Magnetic Lock (often wired into the controller,
 *     but can also be standalone networked devices)
 *   - Router PoE (VPN client + failover, branch network gateway)
 *   - UPS (backup power, 2 jam minimum)
 */
enum DeviceType: string
{
    case Controller = 'controller';
    case FingerprintScanner = 'fingerprint_scanner';
    case Camera = 'camera';
    case Sensor = 'sensor';
    case Buzzer = 'buzzer';
    case Lock = 'lock';
    case Router = 'router';        // P2-17: Router PoE / branch gateway
    case Ups = 'ups';              // P2-18: UPS / backup power source

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Controller => 'Intelligence Controller',
            self::FingerprintScanner => 'Fingerprint Scanner',
            self::Camera => 'IP Camera',
            self::Sensor => 'Sensor',
            self::Buzzer => 'Alarm Buzzer',
            self::Lock => 'Magnetic Lock',
            self::Router => 'Router PoE',
            self::Ups => 'UPS',
        };
    }
}
