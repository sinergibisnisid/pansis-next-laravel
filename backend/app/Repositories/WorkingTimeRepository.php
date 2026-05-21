<?php

namespace App\Repositories;

use App\Models\WorkingTime;
use App\Repositories\Contracts\WorkingTimeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WorkingTimeRepository extends BaseRepository implements WorkingTimeRepositoryInterface
{
    public function __construct(WorkingTime $model)
    {
        parent::__construct($model);
    }

    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function getByVault(string $vaultId): Collection
    {
        return $this->model->newQuery()
            ->where('vault_id', $vaultId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    public function isWithinWorkingTime(string $branchId, ?string $vaultId = null): bool
    {
        $now = now();
        $currentDayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');
        $currentDate = $now->format('Y-m-d');

        $query = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('is_holiday', false)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime);

        if ($vaultId) {
            $query->where(function ($q) use ($vaultId) {
                $q->where('vault_id', $vaultId)
                    ->orWhereNull('vault_id');
            });
        }

        $query->where(function ($q) use ($currentDayOfWeek, $currentDate) {
            $q->where('day_of_week', $currentDayOfWeek)
                ->orWhere('specific_date', $currentDate);
        });

        // Check if today is a holiday
        $isHoliday = $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->where('is_holiday', true)
            ->where(function ($q) use ($currentDayOfWeek, $currentDate) {
                $q->where('day_of_week', $currentDayOfWeek)
                    ->orWhere('specific_date', $currentDate);
            })
            ->exists();

        if ($isHoliday) {
            return false;
        }

        return $query->exists();
    }

    public function getActiveSchedules(): Collection
    {
        $now = now();
        $currentDayOfWeek = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');

        return $this->model->newQuery()
            ->where('is_holiday', false)
            ->where('day_of_week', $currentDayOfWeek)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->get();
    }
}
