<?php

namespace App\Enums;

enum FingerPosition: string
{
    case LeftThumb = 'left_thumb';
    case LeftIndex = 'left_index';
    case LeftMiddle = 'left_middle';
    case LeftRing = 'left_ring';
    case LeftPinky = 'left_pinky';
    case RightThumb = 'right_thumb';
    case RightIndex = 'right_index';
    case RightMiddle = 'right_middle';
    case RightRing = 'right_ring';
    case RightPinky = 'right_pinky';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::LeftThumb => 'Left Thumb',
            self::LeftIndex => 'Left Index',
            self::LeftMiddle => 'Left Middle',
            self::LeftRing => 'Left Ring',
            self::LeftPinky => 'Left Pinky',
            self::RightThumb => 'Right Thumb',
            self::RightIndex => 'Right Index',
            self::RightMiddle => 'Right Middle',
            self::RightRing => 'Right Ring',
            self::RightPinky => 'Right Pinky',
        };
    }
}
