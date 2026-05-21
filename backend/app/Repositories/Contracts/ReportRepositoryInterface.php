<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ReportRepositoryInterface extends BaseRepositoryInterface
{
    public function getByUser(string $userId): Collection;
    public function getByStatus(string $status): Collection;
    public function getPending(): Collection;
    public function markGenerating(string $reportId): void;
    public function markCompleted(string $reportId, ?string $filePath = null): void;
    public function markFailed(string $reportId, ?string $reason = null): void;
}
