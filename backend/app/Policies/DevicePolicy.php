<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        if (!$user->hasPermissionTo('devices.view')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $device->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('devices.create');
    }

    public function update(User $user, Device $device): bool
    {
        if (!$user->hasPermissionTo('devices.update')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $device->branch_id;
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->hasPermissionTo('devices.delete');
    }

    public function register(User $user): bool
    {
        return $user->hasPermissionTo('devices.register');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('devices.manage');
    }
}
