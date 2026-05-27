<?php

namespace App\Enums;

/**
 * Logical function a controller I/O channel performs.
 *
 * Per Pansin Access PDF:
 *   Inputs (S1-S4):  door_sensor, exit_button, emergency_button, aux_input
 *   Outputs (R1-R4): magnetic_lock, buzzer, aux_output
 */
enum ChannelFunction: string
{
    // Inputs
    case DoorSensor = 'door_sensor';
    case ExitButton = 'exit_button';
    case EmergencyButton = 'emergency_button';
    case TamperSwitch = 'tamper_switch';
    case AuxInput = 'aux_input';

    // Outputs
    case MagneticLock = 'magnetic_lock';
    case Buzzer = 'buzzer';
    case StrobeLight = 'strobe_light';
    case AuxOutput = 'aux_output';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function inputs(): array
    {
        return [
            self::DoorSensor,
            self::ExitButton,
            self::EmergencyButton,
            self::TamperSwitch,
            self::AuxInput,
        ];
    }

    public static function outputs(): array
    {
        return [
            self::MagneticLock,
            self::Buzzer,
            self::StrobeLight,
            self::AuxOutput,
        ];
    }

    public function direction(): ChannelDirection
    {
        return in_array($this, self::outputs(), true)
            ? ChannelDirection::Output
            : ChannelDirection::Input;
    }

    public function label(): string
    {
        return match ($this) {
            self::DoorSensor => 'Sensor Pintu',
            self::ExitButton => 'Tombol Keluar',
            self::EmergencyButton => 'Tombol Darurat',
            self::TamperSwitch => 'Switch Tamper',
            self::AuxInput => 'Auxiliary Input',
            self::MagneticLock => 'Magnetic Lock',
            self::Buzzer => 'Alarm Buzzer',
            self::StrobeLight => 'Lampu Strobe',
            self::AuxOutput => 'Auxiliary Output',
        };
    }
}
