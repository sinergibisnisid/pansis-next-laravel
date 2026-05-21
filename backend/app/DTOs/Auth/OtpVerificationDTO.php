<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

readonly class OtpVerificationDTO
{
    public function __construct(
        public string $userId,
        public string $otp,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: $request->input('user_id'),
            otp: $request->input('otp'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'otp' => $this->otp,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
