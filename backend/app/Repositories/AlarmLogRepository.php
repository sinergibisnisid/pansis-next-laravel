<?php

namespace App\Repositories;

use App\Models\AlarmLog;
use App\Repositories\Contracts\AlarmLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AlarmLogRepository extends BaseRepository implements AlarmLogRepositoryInterface
{
    public function __construct(AlarmLog $model)
    {
        parent::__construct($model);
    }

    public function getActiveAlarms(): Collection
    {
        return $this->model->newQuery()
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()
            ->where('branch_id', $branchId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getBySeverity(string $severity): Collection
    {
        return $this->model->newQuery()
            ->where('severity', $severity)
            ->orderByDesc('created_at')
            ->get();
    }

    public function acknowledge(string $alarmId, string $userId): void
    {
        $this->model->newQuery()
            ->where('id', $alarmId)
            ->update([
                'acknowledged_at' => now(),
                'acknowledged_by' => $userId,
            ]);
    }

    public function resolve(string $alarmId, string $userId, ?string $resolution = null): void
    {
        $data = [
            'resolved_at' => now(),
            'resolved_by' => $userId,
        ];

        if ($resolution) {
            $data['resolution'] = $resolution;
        }

        $this->model->newQuery()
            ->where('id', $alarmId)
            ->update($data);
    }

    public function getUnresolvedCount(): int
    {
        return $this->model->newQuery()
            ->whereNull('resolved_at')
            ->count();
    }
}
