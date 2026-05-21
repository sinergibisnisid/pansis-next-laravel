<?php

namespace App\Repositories;

use App\Models\NotificationLog;
use App\Repositories\Contracts\NotificationLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class NotificationLogRepository extends BaseRepository implements NotificationLogRepositoryInterface
{
    public function __construct(NotificationLog $model)
    {
        parent::__construct($model);
    }

    public function getPending(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();
    }

    public function markSent(string $notificationId): void
    {
        $this->model->newQuery()
            ->where('id', $notificationId)
            ->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
    }

    public function markFailed(string $notificationId, ?string $reason = null): void
    {
        $data = [
            'status' => 'failed',
            'failed_at' => now(),
        ];

        if ($reason) {
            $data['failure_reason'] = $reason;
        }

        $this->model->newQuery()
            ->where('id', $notificationId)
            ->update($data);

        $this->model->newQuery()
            ->where('id', $notificationId)
            ->increment('retry_count');
    }

    public function getByUser(string $userId): Collection
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getFailedForRetry(int $maxRetries = 3): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'failed')
            ->where('retry_count', '<', $maxRetries)
            ->orderBy('created_at')
            ->get();
    }
}
