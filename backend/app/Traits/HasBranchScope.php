<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasBranchScope
{
    public function scopeForBranch(Builder $query, ?string $branchId): Builder
    {
        if ($branchId) {
            return $query->where('branch_id', $branchId);
        }
        return $query;
    }

    public function scopeForUserBranch(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user && !$user->hasRole(['Super Admin', 'Admin Pusat'])) {
            return $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
