<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('Login', function () {
    test('successful login with username', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'testuser',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $user->assignRole('Operator');

        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'expires_at'],
            ]);
    });

    test('successful login with email', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $user->assignRole('Operator');

        $response = $this->postJson('/api/auth/login', [
            'login' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token', 'expires_at'],
            ]);
    });

    test('login with invalid credentials returns 401', function () {
        User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'testuser',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login' => 'testuser',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    });

    test('login with inactive user returns 422', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'inactiveuser',
            'password' => Hash::make('Password123!'),
            'is_active' => false,
        ]);
        $user->assignRole('Operator');

        $response = $this->postJson('/api/auth/login', [
            'login' => 'inactiveuser',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    });

    test('login with locked account returns 422', function () {
        $user = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'lockeduser',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
            'locked_until' => now()->addMinutes(30),
        ]);
        $user->assignRole('Operator');

        $response = $this->postJson('/api/auth/login', [
            'login' => 'lockeduser',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['login']);
    });

    test('login rate limiting after max 5 attempts', function () {
        User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'ratelimited',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'login' => 'ratelimited',
                'password' => 'WrongPassword!',
            ]);
        }

        $response->assertStatus(422);
    });
});

describe('Logout', function () {
    test('logout invalidates token', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/auth/logout');

        $response->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });

    test('logout all devices', function () {
        $user = $this->authenticateSuperAdmin();

        // Create additional tokens
        $user->createToken('device-2');
        $user->createToken('device-3');

        $response = $this->postJson('/api/auth/logout-all');

        $response->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    });
});

describe('Token', function () {
    test('refresh token', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['token', 'expires_at'],
            ]);
    });
});

describe('Me', function () {
    test('get authenticated user', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->getJson('/api/auth/me');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $user->id,
                'username' => $user->username,
            ]);
    });
});
