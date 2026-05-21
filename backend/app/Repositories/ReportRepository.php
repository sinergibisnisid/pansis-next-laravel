<?php

namespace App\Repositories;

use App\Models\Report;
use App\Repositories\Contracts\ReportRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository extends BaseRepository implements ReportRepositoryInterface
{
    public function __construct(Report $model)
    {
        parent::__construct($model);
    }

    public function getByUser(string $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
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

    public function getPending(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();
    }

    public function markGenerating(string $reportId): void
    {
        $this->model->newQuery()
            ->where('id', $reportId)
            ->update([
                'status' => 'generating',
                'started_at' => now(),
            ]);
    }

    public function markCompleted(string $reportId, ?string $filePath = null): void
    {
        $data = [
            'status' => 'completed',
            'completed_at' => now(),
        ];

        if ($filePath) {
            $data['file_path'] = $filePath;
        }

        $this->model->newQuery()
            ->where('id', $reportId)
            ->update($data);
    }

    public function markFailed(string $reportId, ?string $reason = null): void
    {
        $data = [
            'status' => 'failed',
            'failed_at' => now(),
        ];

        if ($reason) {
            $data['failure_reason'] = $reason;
        }

        $this->model->newQuery()
            ->where('id', $reportId)
            ->update($data);
    }
}
