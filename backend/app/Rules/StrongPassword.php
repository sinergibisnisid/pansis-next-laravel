<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) {
            $fail('Password harus minimal 8 karakter.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('Password harus mengandung minimal satu huruf besar.');
            return;
        }

        if (!preg_match('/[a-z]/', $value)) {
            $fail('Password harus mengandung minimal satu huruf kecil.');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('Password harus mengandung minimal satu angka.');
            return;
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
            $fail('Password harus mengandung minimal satu simbol.');
            return;
        }
    }
}
