<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginDTO;
use App\Services\AuthService;

class LoginAction
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function execute(LoginDTO $dto): array
    {
        return $this->authService->login($dto);
    }
}
