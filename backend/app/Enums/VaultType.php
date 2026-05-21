<?php

namespace App\Enums;

enum VaultType: string
{
    case Main = 'main';
    case Secondary = 'secondary';
    case Atm = 'atm';
    case SafeDeposit = 'safe_deposit';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Main => 'Main',
            self::Secondary => 'Secondary',
            self::Atm => 'ATM',
            self::SafeDeposit => 'Safe Deposit',
        };
    }
}
