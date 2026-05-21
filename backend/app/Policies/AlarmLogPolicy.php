<?php

namespace App\Policies;

use App\Models\AlarmLog;
use App\Models\User;

class AlarmLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('alarms.view');
    }

    public function view(User $user, AlarmLog $alarmLog): bool
    {
        if (!$user->hasPermissionTo('alarms.view')) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->branch_id === $alarmLog->branch_id;
    }

    public function acknowledge(User $user): bool
    {
        return $user->hasPermissionTo('alarms.acknowledge');
    }

    public function resolve(User $user): bool
    {
        return $user->hasPermissionTo('alarms.resolve');
    }

    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('alarms.manage');
    }
}
