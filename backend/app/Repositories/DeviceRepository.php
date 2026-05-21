<?php

namespace App\Repositories;

use App\Models\Device;
use App\Repositories\Contracts\DeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class DeviceRepository extends BaseRepository implements DeviceRepositoryInterface
{
    public function __construct(Device $model)
    {
        parent::__construct($model);
    }

    public function findBySerialNumber(string $serialNumber): ?Model
    {
        return $this->model->newQuery()->where('serial_number', $serialNumber)->first();
    }

    public function findByToken(string $token): ?Model
    {
        return $this->model->newQuery()->where('device_token', $token)->first();
    }

    public function getOnlineDevices(): Collection
    {
        return $this->model->newQuery()->where('status', 'online')->get();
    }

    public function getOfflineDevices(): Collection
    {
        return $this->model->newQuery()->where('status', 'offline')->get();
    }

    public function updateHeartbeat(string $deviceId, array $data): void
    {
        $this->model->newQuery()
            ->where('id', $deviceId)
            ->update(array_merge($data, ['last_heartbeat_at' => now()]));
    }

    public function getByBranch(string $branchId): Collection
    {
        return $this->model->newQuery()->where('branch_id', $branchId)->get();
    }

    public function getByVault(string $vaultId): Collection
    {
        return $this->model->newQuery()->where('vault_id', $vaultId)->get();
    }
}
