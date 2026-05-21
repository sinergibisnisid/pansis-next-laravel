<?php

namespace App\Enums;

enum AuditEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Login = 'login';
    case Logout = 'logout';
    case AccessGranted = 'access_granted';
    case AccessDenied = 'access_denied';
    case AlarmTriggered = 'alarm_triggered';
    case AlarmResolved = 'alarm_resolved';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Updated',
            self::Deleted => 'Deleted',
            self::Restored => 'Restored',
            self::Login => 'Login',
            self::Logout => 'Logout',
            self::AccessGranted => 'Access Granted',
            self::AccessDenied => 'Access Denied',
            self::AlarmTriggered => 'Alarm Triggered',
            self::AlarmResolved => 'Alarm Resolved',
        };
    }
}
