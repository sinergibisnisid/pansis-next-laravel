<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface DeviceRepositoryInterface extends BaseRepositoryInterface
{
    public function findBySerialNumber(string $serialNumber): ?Model;
    public function findByToken(string $token): ?Model;
    public function getOnlineDevices(): Collection;
    public function getOfflineDevices(): Collection;
    public function updateHeartbeat(string $deviceId, array $data): void;
    public function getByBranch(string $branchId): Collection;
    public function getByVault(string $vaultId): Collection;
}
