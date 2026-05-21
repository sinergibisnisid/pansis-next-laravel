<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface NotificationLogRepositoryInterface extends BaseRepositoryInterface
{
    public function getPending(): Collection;
    public function markSent(string $notificationId): void;
    public function markFailed(string $notificationId, ?string $reason = null): void;
    public function getByUser(string $userId): Collection;
    public function getFailedForRetry(int $maxRetries = 3): Collection;
}
