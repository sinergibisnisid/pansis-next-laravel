<?php

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;

uses(Tests\Traits\AuthenticatedUser::class);

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->branch = Branch::factory()->create(['organization_id' => $this->organization->id]);
});

describe('User CRUD', function () {
    test('list users with role filtering', function () {
        $user = $this->authenticateSuperAdmin();

        User::factory()->count(3)->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ])->each(fn ($u) => $u->assignRole('Operator'));

        User::factory()->count(2)->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ])->each(fn ($u) => $u->assignRole('Viewer'));

        $response = $this->getJson('/api/users?role=Operator');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    });

    test('create user with role assignment', function () {
        $user = $this->authenticateSuperAdmin();

        $response = $this->postJson('/api/users', [
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'newoperator',
            'email' => 'newoperator@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'full_name' => 'New Operator',
            'phone' => '081234567890',
            'role' => 'Operator',
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['username' => 'newoperator']);

        $this->assertDatabaseHas('users', [
            'username' => 'newoperator',
            'email' => 'newoperator@example.com',
        ]);
    });

    test('update user', function () {
        $user = $this->authenticateSuperAdmin();

        $targetUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'full_name' => 'Old Name',
        ]);

        $response = $this->putJson("/api/users/{$targetUser->id}", [
            'full_name' => 'Updated Name',
            'phone' => '089876543210',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['full_name' => 'Updated Name']);
    });

    test('delete user', function () {
        $user = $this->authenticateSuperAdmin();

        $targetUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->deleteJson("/api/users/{$targetUser->id}");

        $response->assertOk();

        $this->assertSoftDeleted('users', ['id' => $targetUser->id]);
    });

    test('toggle user active status', function () {
        $user = $this->authenticateSuperAdmin();

        $targetUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $response = $this->patchJson("/api/users/{$targetUser->id}/toggle-active");

        $response->assertOk();

        $targetUser->refresh();
        expect($targetUser->is_active)->toBeFalse();
    });

    test('update password', function () {
        $user = $this->authenticateSuperAdmin();

        $targetUser = User::factory()->create([
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->patchJson("/api/users/{$targetUser->id}/password", [
            'password' => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ]);

        $response->assertOk();
    });
});

describe('Authorization', function () {
    test('unauthorized access returns 403', function () {
        $user = $this->authenticateViewer();

        $response = $this->postJson('/api/users', [
            'organization_id' => $this->organization->id,
            'branch_id' => $this->branch->id,
            'username' => 'unauthorized',
            'email' => 'unauthorized@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'full_name' => 'Unauthorized User',
            'role' => 'Operator',
        ]);

        $response->assertForbidden();
    });
});
