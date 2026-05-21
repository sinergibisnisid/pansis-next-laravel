<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface LivestreamSessionRepositoryInterface extends BaseRepositoryInterface
{
    public function getActiveSessions(): Collection;
    public function getByDevice(string $deviceId): Collection;
    public function startSession(array $data): Model;
    public function stopSession(string $sessionId): void;
}
