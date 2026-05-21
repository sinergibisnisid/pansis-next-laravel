<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $this->user->assignRole('Operator');
});

describe('Send OTP', function () {
    test('send OTP successfully', function () {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/auth/otp/send');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        // Verify OTP was cached
        $cacheKey = "otp:{$this->user->id}";
        expect(Cache::has($cacheKey))->toBeTrue();
    });
});

describe('Verify OTP', function () {
    test('verify valid OTP', function () {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);

        // Manually generate and cache OTP
        $otp = '123456';
        $cacheKey = "otp:{$this->user->id}";
        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(5));

        $response = $this->postJson('/api/auth/otp/verify', [
            'otp' => $otp,
        ]);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        // Verify OTP was removed from cache
        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('verify invalid OTP returns error', function () {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);

        // Manually generate and cache OTP
        $otp = '123456';
        $cacheKey = "otp:{$this->user->id}";
        Cache::put($cacheKey, Hash::make($otp), now()->addMinutes(5));

        $response = $this->postJson('/api/auth/otp/verify', [
            'otp' => '999999',
        ]);

        $response->assertStatus(422);
    });

    test('OTP expires after 5 minutes', function () {
        \Laravel\Sanctum\Sanctum::actingAs($this->user);

        // Manually generate and cache OTP with past expiry
        $otp = '123456';
        $cacheKey = "otp:{$this->user->id}";
        Cache::put($cacheKey, Hash::make($otp), now()->subSecond());

        // Travel forward in time
        $this->travel(6)->minutes();

        $response = $this->postJson('/api/auth/otp/verify', [
            'otp' => $otp,
        ]);

        $response->assertStatus(422);
    });
});
