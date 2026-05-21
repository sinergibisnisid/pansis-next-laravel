<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage, $columns);
    }

    public function find(string $id, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    public function findOrFail(string $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function update(string $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);

        return $record->fresh();
    }

    public function delete(string $id): bool
    {
        $record = $this->findOrFail($id);

        return $record->delete();
    }

    public function findByField(string $field, mixed $value, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->where($field, $value)->get($columns);
    }

    public function findOneByField(string $field, mixed $value, array $columns = ['*']): ?Model
    {
        return $this->model->newQuery()->where($field, $value)->first($columns);
    }

    public function where(array $conditions, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->where($conditions)->get($columns);
    }

    public function count(array $conditions = []): int
    {
        $query = $this->model->newQuery();

        if (!empty($conditions)) {
            $query->where($conditions);
        }

        return $query->count();
    }
}
