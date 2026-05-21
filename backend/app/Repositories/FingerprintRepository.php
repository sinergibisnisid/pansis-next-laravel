<?php

namespace App\Repositories;

use App\Models\Fingerprint;
use App\Repositories\Contracts\FingerprintRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FingerprintRepository extends BaseRepository implements FingerprintRepositoryInterface
{
    public function __construct(Fingerprint $model)
    {
        parent::__construct($model);
    }

    public function findByDeviceAndFingerprintId(string $deviceId, string $fingerprintId): ?Model
    {
        return $this->model->newQuery()
            ->where('device_id', $deviceId)
            ->where('fingerprint_id', $fingerprintId)
            ->first();
    }

    public function getByUser(string $userId): Collection
    {
        return $this->model->newQuery()->where('user_id', $userId)->get();
    }

    public function getByDevice(string $deviceId): Collection
    {
        return $this->model->newQuery()->where('device_id', $deviceId)->get();
    }

    public function validateFingerprint(string $fingerprintId, string $userId): bool
    {
        return $this->model->newQuery()
            ->where('fingerprint_id', $fingerprintId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->exists();
    }
}
