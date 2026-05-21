<?php

use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Private channel for branch monitoring - user must belong to branch or be admin
Broadcast::channel('branch.{branchId}', function (User $user, string $branchId) {
    if ($user->hasRole(['Super Admin', 'Admin Pusat'])) {
        return true;
    }

    return $user->branch_id === $branchId;
});

// Private channel for vault monitoring - user must have access to vault's branch
Broadcast::channel('vault.{vaultId}', function (User $user, string $vaultId) {
    if ($user->hasRole(['Super Admin', 'Admin Pusat'])) {
        return true;
    }

    $vault = Vault::find($vaultId);

    return $vault && $user->branch_id === $vault->branch_id;
});

// Private channel for alarms - admin and security roles
Broadcast::channel('alarms', function (User $user) {
    return $user->hasRole(['Super Admin', 'Admin Pusat', 'Admin Cabang', 'Security']);
});

// Private channel for devices monitoring
Broadcast::channel('devices', function (User $user) {
    return $user->hasRole(['Super Admin', 'Admin Pusat', 'Admin Cabang', 'Operator', 'Maintenance']);
});

// Presence channel for monitoring dashboard - shows who's online
Broadcast::channel('monitoring.dashboard', function (User $user) {
    if ($user->hasAnyRole(['Super Admin', 'Admin Pusat', 'Admin Cabang', 'Operator', 'Security'])) {
        return [
            'id' => $user->id,
            'name' => $user->full_name,
            'role' => $user->roles->first()?->name,
            'branch_id' => $user->branch_id,
        ];
    }

    return false;
});

// Private channel for user-specific notifications
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});
