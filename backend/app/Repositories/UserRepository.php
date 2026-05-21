<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByUsername(string $username): ?Model
    {
        return $this->model->newQuery()->where('username', $username)->first();
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    public function incrementFailedLogin(string $userId): void
    {
        $this->model->newQuery()
            ->where('id', $userId)
            ->increment('failed_login_attempts');
    }

    public function resetFailedLogin(string $userId): void
    {
        $this->model->newQuery()
            ->where('id', $userId)
            ->update(['failed_login_attempts' => 0]);
    }

    public function updateLastLogin(string $userId, ?string $ipAddress = null): void
    {
        $data = ['last_login_at' => now()];

        if ($ipAddress) {
            $data['last_login_ip'] = $ipAddress;
        }

        $this->model->newQuery()
            ->where('id', $userId)
            ->update($data);
    }
}
