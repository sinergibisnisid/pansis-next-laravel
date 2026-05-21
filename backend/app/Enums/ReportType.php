<?php

namespace App\Enums;

enum ReportType: string
{
    case Audit = 'audit';
    case Activity = 'activity';
    case Access = 'access';
    case Alarm = 'alarm';
    case Maintenance = 'maintenance';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Audit => 'Audit',
            self::Activity => 'Activity',
            self::Access => 'Access',
            self::Alarm => 'Alarm',
            self::Maintenance => 'Maintenance',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Monthly => 'Monthly',
        };
    }
}
