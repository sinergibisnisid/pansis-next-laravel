<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MacAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Validates MAC address format: XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX
        $pattern = '/^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/';

        if (!preg_match($pattern, $value)) {
            $fail('Format MAC address tidak valid. Gunakan format XX:XX:XX:XX:XX:XX atau XX-XX-XX-XX-XX-XX.');
        }
    }
}
