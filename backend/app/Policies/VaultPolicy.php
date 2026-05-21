<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vault;

class VaultPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('vaults.view');
    }

    public function view(User $user, Vault $vault): bool
    {
        if (!$user->hasPermissionTo('vaults.view')) {
            return false;
        }

        // Super Admin can view all vaults
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Branch access check
        return $user->branch_id === $vault->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('vaults.create');
    }

    public function update(User $user, Vault $vault): bool
    {
        if (!$user->hasPermissionTo('vaults.update')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $vault->branch_id;
    }

    public function delete(User $user, Vault $vault): bool
    {
        return $user->hasPermissionTo('vaults.delete');
    }

    public function open(User $user, Vault $vault): bool
    {
        if (!$user->hasPermissionTo('vaults.open')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $vault->branch_id;
    }

    public function close(User $user, Vault $vault): bool
    {
        if (!$user->hasPermissionTo('vaults.close')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $vault->branch_id;
    }

    public function monitor(User $user): bool
    {
        return $user->hasPermissionTo('vaults.monitor');
    }
}
