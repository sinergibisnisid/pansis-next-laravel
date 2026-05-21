<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branches.view')) {
            return false;
        }

        // Admin or Super Admin can view all branches
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            return true;
        }

        // Others can only view their own branch
        return $user->branch_id === $branch->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.update');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermissionTo('branches.delete');
    }
}
