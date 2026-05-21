<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface FingerprintRepositoryInterface extends BaseRepositoryInterface
{
    public function findByDeviceAndFingerprintId(string $deviceId, string $fingerprintId): ?Model;
    public function getByUser(string $userId): Collection;
    public function getByDevice(string $deviceId): Collection;
    public function validateFingerprint(string $fingerprintId, string $userId): bool;
}
