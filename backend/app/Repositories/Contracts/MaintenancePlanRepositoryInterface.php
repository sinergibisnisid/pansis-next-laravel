<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface MaintenancePlanRepositoryInterface extends BaseRepositoryInterface
{
    public function getUpcoming(int $days = 7): Collection;
    public function getOverdue(): Collection;
    public function getByBranch(string $branchId): Collection;
    public function getByStatus(string $status): Collection;
    public function markCompleted(string $planId, ?string $notes = null): void;
}
