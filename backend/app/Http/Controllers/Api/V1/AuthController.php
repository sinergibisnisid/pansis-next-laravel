<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\SendOtpAction;
use App\Actions\Auth\VerifyOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        if (!$result) {
            return $this->errorResponse('Invalid credentials', 401);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
            'user' => $result['user'],
        ], 'Login successful');
    }

    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return $this->successResponse(message: 'Logged out successfully');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $this->authService->logoutAllDevices($request->user());

        return $this->successResponse(message: 'Logged out from all devices successfully');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['roles.permissions', 'branch', 'organization']);

        return $this->successResponse([
            'user' => $user,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user());

        if (!$result) {
            return $this->errorResponse('Unable to refresh token', 401);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
        ], 'Token refreshed successfully');
    }

    public function sendOtp(Request $request, SendOtpAction $action): JsonResponse
    {
        $result = $action->execute($request->user());

        if (!$result) {
            return $this->errorResponse('Failed to send OTP', 500);
        }

        return $this->successResponse(message: 'OTP sent successfully');
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $result = $action->execute($request->user(), $request->validated('otp'));

        if (!$result) {
            return $this->errorResponse('Invalid or expired OTP', 422);
        }

        return $this->successResponse([
            'verified' => true,
        ], 'OTP verified successfully');
    }

    public function loginHistory(Request $request): JsonResponse
    {
        $logs = $request->user()
            ->auditLogs()
            ->where('event', 'login')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 15));

        return $this->paginatedResponse($logs, 'Login history retrieved');
    }
}
