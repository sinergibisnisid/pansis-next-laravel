<?php

namespace Tests\Traits;

use App\Models\User;
use App\Models\Branch;
use App\Models\Organization;
use Laravel\Sanctum\Sanctum;

trait AuthenticatedUser
{
    protected function authenticateAs(string $role = 'Super Admin'): User
    {
        $organization = Organization::factory()->create();
        $branch = Branch::factory()->create(['organization_id' => $organization->id]);

        $user = User::factory()->create([
            'organization_id' => $organization->id,
            'branch_id' => $branch->id,
        ]);

        $user->assignRole($role);
        Sanctum::actingAs($user);

        return $user;
    }

    protected function authenticateSuperAdmin(): User
    {
        return $this->authenticateAs('Super Admin');
    }

    protected function authenticateOperator(): User
    {
        return $this->authenticateAs('Operator');
    }

    protected function authenticateViewer(): User
    {
        return $this->authenticateAs('Viewer');
    }
}
