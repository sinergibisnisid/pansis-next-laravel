<?php

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

readonly class LoginDTO
{
    public function __construct(
        public string $login,
        public string $password,
        public ?string $deviceName = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            login: $request->input('login'),
            password: $request->input('password'),
            deviceName: $request->input('device_name', 'web'),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    public function toArray(): array
    {
        return [
            'login' => $this->login,
            'password' => $this->password,
            'device_name' => $this->deviceName,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
