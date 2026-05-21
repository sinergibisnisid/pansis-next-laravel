<?php

namespace App\Repositories;

use App\Models\MaintenancePlan;
use App\Repositories\Contracts\MaintenancePlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MaintenancePlanRepository extends BaseRepository implements MaintenancePlanRepositoryInterface
{
    public function __construct(MaintenancePlan $model)
    {
        parent::__construct($model);
    }

    public function getUpcoming(int $days = 7): Collection
    {
        return $this->model->newQuery()
            ->where('status', '!=', 'completed')
            ->where('scheduled_date', '>=', now())
            ->where('scheduled_date', '<=', now()->addDays($days))
            ->orderBy('scheduled_date')
            ->get();
    }

    public function getOverdue(): Collection
    {
        return $this->model->newQuery()
            ->where('status', '!=', 'completed')
            ->where('due_date', '<', now())
            ->orderBy('due_date')
            ->get();
    }

    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByStatus(string $status): Collection
    {
        return $this->model->newQuery()
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();
    }

    public function markCompleted(string $planId, ?string $notes = null): void
    {
        $data = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($notes) {
            $data['notes'] = $notes;
        }

        $this->model->newQuery()
            ->where('id', $planId)
            ->update($data);
    }
}
