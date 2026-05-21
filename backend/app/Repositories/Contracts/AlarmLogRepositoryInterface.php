<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface AlarmLogRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveAlarms(): Collection;
    public function getByBranch(string $branchId): Collection;
    public function getBySeverity(string $severity): Collection;
    public function acknowledge(string $alarmId, string $userId): void;
    public function resolve(string $alarmId, string $userId, ?string $resolution = null): void;
    public function getUnresolvedCount(): int;
}
