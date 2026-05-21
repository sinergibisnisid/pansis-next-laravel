<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\OtpVerificationDTO;
use App\Services\AuthService;

class VerifyOtpAction
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function execute(OtpVerificationDTO $dto): bool
    {
        return $this->authService->verifyOtp($dto);
    }
}
