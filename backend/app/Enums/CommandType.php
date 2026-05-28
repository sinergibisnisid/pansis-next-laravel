<?php

namespace App\Enums;

/**
 * Categories of hardware command issued from backend to controller relays.
 *
 * Per Pansin Access PDF: magnetic lock + alarm buzzer are the two safety-critical
 * relay outputs. Strobe + aux are nice-to-have.
 */
enum CommandType: string
{
    case LockRelease = 'lock_release';
    case LockEngage = 'lock_engage';
    case BuzzerActivate = 'buzzer_activate';
    case BuzzerDeactivate = 'buzzer_deactivate';
    case StrobeOn = 'strobe_on';
    case StrobeOff = 'strobe_off';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * MQTT topic template — substitute {vault_id} at publish time.
     */
    public function topicTemplate(): string
    {
        return match ($this) {
            self::LockRelease => 'lock/{vault_id}/release',
            self::LockEngage => 'lock/{vault_id}/engage',
            self::BuzzerActivate => 'buzzer/{vault_id}/activate',
            self::BuzzerDeactivate => 'buzzer/{vault_id}/deactivate',
            self::StrobeOn => 'strobe/{vault_id}/on',
            self::StrobeOff => 'strobe/{vault_id}/off',
        };
    }

    /**
     * Whether this command is safety-critical (must use QoS 2 + retry).
     */
    public function isSafetyCritical(): bool
    {
        return in_array($this, [
            self::LockRelease,
            self::LockEngage,
            self::BuzzerActivate,
        ], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::LockRelease => 'Buka Kunci',
            self::LockEngage => 'Kunci Pintu',
            self::BuzzerActivate => 'Aktifkan Buzzer',
            self::BuzzerDeactivate => 'Matikan Buzzer',
            self::StrobeOn => 'Lampu Strobe Nyala',
            self::StrobeOff => 'Lampu Strobe Mati',
        };
    }
}
