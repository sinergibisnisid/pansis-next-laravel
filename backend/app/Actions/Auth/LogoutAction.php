<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\AuthService;

class LogoutAction
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function execute(User $user, ?string $tokenId = null): void
    {
        $this->authService->logout($user, $tokenId);
    }
}
