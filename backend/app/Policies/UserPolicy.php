<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.view');
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.view')) {
            return false;
        }

        // Super Admin sees all users
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Others can only see users in the same branch
        return $user->branch_id === $model->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.update')) {
            return false;
        }

        // Cannot update a user with a higher role
        if ($this->hasHigherRole($model, $user)) {
            return false;
        }

        return true;
    }

    public function delete(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.delete')) {
            return false;
        }

        // Cannot delete self
        if ($user->id === $model->id) {
            return false;
        }

        // Cannot delete a user with a higher role
        if ($this->hasHigherRole($model, $user)) {
            return false;
        }

        return true;
    }

    public function manageRoles(User $user): bool
    {
        return $user->hasPermissionTo('users.manage-roles');
    }

    /**
     * Determine if the target user has a higher role than the acting user.
     */
    protected function hasHigherRole(User $target, User $actor): bool
    {
        $roleHierarchy = [
            'Super Admin' => 4,
            'Admin' => 3,
            'Branch Manager' => 2,
            'Operator' => 1,
            'Viewer' => 0,
        ];

        $targetHighestRole = 0;
        $actorHighestRole = 0;

        foreach ($target->roles as $role) {
            $level = $roleHierarchy[$role->name] ?? 0;
            $targetHighestRole = max($targetHighestRole, $level);
        }

        foreach ($actor->roles as $role) {
            $level = $roleHierarchy[$role->name] ?? 0;
            $actorHighestRole = max($actorHighestRole, $level);
        }

        return $targetHighestRole > $actorHighestRole;
    }
}
