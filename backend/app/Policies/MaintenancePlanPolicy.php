<?php

namespace App\Policies;

use App\Models\MaintenancePlan;
use App\Models\User;

class MaintenancePlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('maintenance.view');
    }

    public function view(User $user, MaintenancePlan $plan): bool
    {
        if (!$user->hasPermissionTo('maintenance.view')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $plan->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('maintenance.create');
    }

    public function update(User $user, MaintenancePlan $plan): bool
    {
        if (!$user->hasPermissionTo('maintenance.update')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $plan->branch_id;
    }

    public function complete(User $user): bool
    {
        return $user->hasPermissionTo('maintenance.complete');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('maintenance.manage');
    }
}
