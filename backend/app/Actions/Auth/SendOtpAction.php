<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuthService;
use App\Services\NotificationService;

class SendOtpAction
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly NotificationService $notificationService,
    ) {}

    public function execute(User $user): void
    {
        $otp = $this->authService->generateOtp($user);

        $this->notificationService->sendWhatsApp(
            recipient: $user->phone_number,
            title: 'OTP Verification',
            body: "Your OTP code is: {$otp}. Valid for 5 minutes. Do not share this code with anyone.",
        );
    }
}
