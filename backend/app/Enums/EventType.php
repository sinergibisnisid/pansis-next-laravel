<?php

namespace App\Enums;

enum EventType: string
{
    case Alarm = 'alarm';
    case AccessGranted = 'access_granted';
    case AccessDenied = 'access_denied';
    case MaintenanceDue = 'maintenance_due';
    case SessionTimeout = 'session_timeout';
    case DeviceOffline = 'device_offline';
    case DailyReport = 'daily_report';
    case WeeklyReport = 'weekly_report';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Alarm => 'Alarm',
            self::AccessGranted => 'Access Granted',
            self::AccessDenied => 'Access Denied',
            self::MaintenanceDue => 'Maintenance Due',
            self::SessionTimeout => 'Session Timeout',
            self::DeviceOffline => 'Device Offline',
            self::DailyReport => 'Daily Report',
            self::WeeklyReport => 'Weekly Report',
        };
    }
}
