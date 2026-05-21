<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use App\Services\AuthService;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\OtpVerificationDTO;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->auditService = Mockery::mock(AuditService::class);
    $this->notificationService = Mockery::mock(NotificationService::class);

    $this->authService = new AuthService(
        $this->userRepository,
        $this->auditService,
        $this->notificationService,
    );

    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('resolveUser', function () {
    test('resolveUser finds by username', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'johndoe',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->with('johndoe')
            ->andReturn($user);

        $this->userRepository->shouldReceive('resetFailedLogin')->once();
        $this->userRepository->shouldReceive('updateLastLogin')->once();
        $this->auditService->shouldReceive('log')->once();

        $dto = new LoginDTO(
            login: 'johndoe',
            password: 'Password123!',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        $result = $this->authService->login($dto);

        expect($result)->toHaveKeys(['user', 'token', 'expires_at']);
        expect($result['user']->username)->toBe('johndoe');
    });

    test('resolveUser finds by email', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->with('john@example.com')
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->with('john@example.com')
            ->andReturn($user);

        $this->userRepository->shouldReceive('resetFailedLogin')->once();
        $this->userRepository->shouldReceive('updateLastLogin')->once();
        $this->auditService->shouldReceive('log')->once();

        $dto = new LoginDTO(
            login: 'john@example.com',
            password: 'Password123!',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        $result = $this->authService->login($dto);

        expect($result['user']->email)->toBe('john@example.com');
    });
});

describe('Account Locking', function () {
    test('isAccountLocked returns true when locked', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'lockeduser',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
            'locked_until' => now()->addMinutes(30),
        ]);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->with('lockeduser')
            ->andReturn($user);

        $dto = new LoginDTO(
            login: 'lockeduser',
            password: 'Password123!',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        expect(fn () => $this->authService->login($dto))
            ->toThrow(ValidationException::class);
    });

    test('handleFailedLogin increments counter', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'failuser',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
            'failed_login_count' => 0,
        ]);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->with('failuser')
            ->andReturn($user);

        $this->userRepository
            ->shouldReceive('incrementFailedLogin')
            ->with($user->id)
            ->once();

        $this->auditService->shouldReceive('log')->once();

        $dto = new LoginDTO(
            login: 'failuser',
            password: 'WrongPassword!',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        expect(fn () => $this->authService->login($dto))
            ->toThrow(ValidationException::class);
    });

    test('handleFailedLogin locks after 5 attempts', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'lockme',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
            'failed_login_count' => 4,
        ]);

        $this->userRepository
            ->shouldReceive('findByUsername')
            ->with('lockme')
            ->andReturn($user);

        $this->userRepository
            ->shouldReceive('incrementFailedLogin')
            ->with($user->id)
            ->once();

        $this->auditService->shouldReceive('log')->once();
        $this->notificationService->shouldReceive('sendSuspiciousLoginAlert')->once();

        $dto = new LoginDTO(
            login: 'lockme',
            password: 'WrongPassword!',
            ipAddress: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        expect(fn () => $this->authService->login($dto))
            ->toThrow(ValidationException::class);
    });
});

describe('OTP', function () {
    test('generateOtp returns 6 digit string', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ]);

        $otp = $this->authService->generateOtp($user);

        expect($otp)->toBeString()
            ->toHaveLength(6)
            ->toMatch('/^\d{6}$/');
    });

    test('verifyOtp with valid OTP', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ]);

        $otp = '123456';
        Cache::put("otp:{$user->id}", Hash::make($otp), now()->addMinutes(5));

        $this->userRepository
            ->shouldReceive('findOrFail')
            ->with($user->id)
            ->andReturn($user);

        $dto = new OtpVerificationDTO(
            userId: $user->id,
            otp: $otp,
        );

        $result = $this->authService->verifyOtp($dto);

        expect($result)->toBeTrue();
        expect(Cache::has("otp:{$user->id}"))->toBeFalse();
    });

    test('verifyOtp with invalid OTP', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ]);

        $otp = '123456';
        Cache::put("otp:{$user->id}", Hash::make($otp), now()->addMinutes(5));

        $dto = new OtpVerificationDTO(
            userId: $user->id,
            otp: '999999',
        );

        $result = $this->authService->verifyOtp($dto);

        expect($result)->toBeFalse();
    });
});
