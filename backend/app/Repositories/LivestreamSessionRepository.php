<?php

namespace App\Repositories;

use App\Models\LivestreamSession;
use App\Repositories\Contracts\LivestreamSessionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class LivestreamSessionRepository extends BaseRepository implements LivestreamSessionRepositoryInterface
{
    public function __construct(LivestreamSession $model)
    {
        parent::__construct($model);
    }

    public function getActiveSessions(): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->orderByDesc('started_at')
            ->get();
    }

    public function getByDevice(string $deviceId): Collection
    {
        return $this->model->newQuery()
            ->where('device_id', $deviceId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function startSession(array $data): Model
    {
        return $this->model->newQuery()->create(array_merge($data, [
            'status' => 'active',
            'started_at' => now(),
        ]));
    }

    public function stopSession(string $sessionId): void
    {
        $this->model->newQuery()
            ->where('id', $sessionId)
            ->update([
                'status' => 'stopped',
                'stopped_at' => now(),
            ]);
    }
}
