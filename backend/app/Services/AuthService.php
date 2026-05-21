<?php

namespace App\Services;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\OtpVerificationDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Enums\AuditEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AuditService $auditService,
        private readonly NotificationService $notificationService,
    ) {}

    public function login(LoginDTO $dto): array
    {
        $user = $this->resolveUser($dto->login);

        if (!$user) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($this->isAccountLocked($user)) {
            throw ValidationException::withMessages([
                'login' => ['Account is locked. Please try again later.'],
            ]);
        }

        if (!Hash::check($dto->password, $user->password)) {
            $this->handleFailedLogin($user, $dto);
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Account is deactivated. Contact administrator.'],
            ]);
        }

        $this->userRepository->resetFailedLogin($user->id);
        $this->userRepository->updateLastLogin($user->id, $dto->ipAddress);

        $token = $user->createToken($dto->deviceName ?? 'web', ['*'], now()->addHours(24));

        $this->auditService->log(
            user: $user,
            event: AuditEvent::Login,
            auditable: $user,
            metadata: ['ip_address' => $dto->ipAddress, 'user_agent' => $dto->userAgent]
        );

        return [
            'user' => $user,
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    public function logout(User $user, ?string $tokenId = null): void
    {
        if ($tokenId) {
            $user->tokens()->where('id', $tokenId)->delete();
        } else {
            $user->currentAccessToken()->delete();
        }

        $this->auditService->log(
            user: $user,
            event: AuditEvent::Logout,
            auditable: $user,
        );
    }

    public function logoutAllDevices(User $user): void
    {
        $user->tokens()->delete();

        $this->auditService->log(
            user: $user,
            event: AuditEvent::Logout,
            auditable: $user,
            metadata: ['scope' => 'all_devices']
        );
    }

    public function generateOtp(User $user): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = "otp:{$user->id}";

        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(5));

        return $otp;
    }

    public function verifyOtp(OtpVerificationDTO $dto): bool
    {
        $cacheKey = "otp:{$dto->userId}";
        $hashedOtp = Cache::get($cacheKey);

        if (!$hashedOtp || !Hash::check($dto->otp, $hashedOtp)) {
            return false;
        }

        Cache::forget($cacheKey);

        $user = $this->userRepository->findOrFail($dto->userId);
        $user->update(['otp_verified_at' => now()]);

        return true;
    }

    public function refreshToken(User $user, string $deviceName = 'web'): array
    {
        $user->currentAccessToken()->delete();
        $token = $user->createToken($deviceName, ['*'], now()->addHours(24));

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at,
        ];
    }

    private function resolveUser(string $login): ?User
    {
        $user = $this->userRepository->findByUsername($login);
        if (!$user) {
            $user = $this->userRepository->findByEmail($login);
        }
        return $user;
    }

    private function isAccountLocked(User $user): bool
    {
        return $user->locked_until && $user->locked_until->isFuture();
    }

    private function handleFailedLogin(User $user, LoginDTO $dto): void
    {
        $this->userRepository->incrementFailedLogin($user->id);

        $failedCount = $user->failed_login_count + 1;

        if ($failedCount >= 5) {
            $user->update(['locked_until' => now()->addMinutes(30)]);

            $this->notificationService->sendSuspiciousLoginAlert($user, $dto->ipAddress);
        }

        $this->auditService->log(
            user: $user,
            event: AuditEvent::Login,
            auditable: $user,
            metadata: [
                'status' => 'failed',
                'ip_address' => $dto->ipAddress,
                'failed_count' => $failedCount,
            ]
        );
    }
}
