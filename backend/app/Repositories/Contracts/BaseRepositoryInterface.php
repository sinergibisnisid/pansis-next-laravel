<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;
    public function find(string $id, array $columns = ['*']): ?Model;
    public function findOrFail(string $id, array $columns = ['*']): Model;
    public function create(array $data): Model;
    public function update(string $id, array $data): Model;
    public function delete(string $id): bool;
    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection;
    public function findOneByField(string $field, mixed $value, array $columns = ['*']): ?Model;
    public function where(array $conditions, array $columns = ['*']): Collection;
    public function count(array $conditions = []): int;
}
