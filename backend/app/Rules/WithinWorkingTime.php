<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\WorkingTimeService;

class WithinWorkingTime implements ValidationRule
{
    public function __construct(
        private readonly string $branchId,
        private readonly ?string $vaultId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $workingTimeService = app(WorkingTimeService::class);

        if (!$workingTimeService->isWithinWorkingTime($this->branchId, $this->vaultId)) {
            $fail('Akses ditolak. Saat ini di luar jam kerja yang ditentukan.');
        }
    }
}
