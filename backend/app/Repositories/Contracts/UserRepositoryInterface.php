<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUsername(string $username): ?Model;
    public function findByEmail(string $email): ?Model;
    public function incrementFailedLogin(string $userId): void;
    public function resetFailedLogin(string $userId): void;
    public function updateLastLogin(string $userId, ?string $ipAddress = null): void;
}
