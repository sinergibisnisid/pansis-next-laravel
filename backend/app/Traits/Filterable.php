<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        foreach ($this->getFilterableFields() as $field => $type) {
            $value = $request->input($field);
            if ($value === null) continue;

            match ($type) {
                'exact' => $query->where($field, $value),
                'like' => $query->where($field, 'ILIKE', "%{$value}%"),
                'boolean' => $query->where($field, filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                'date' => $query->whereDate($field, $value),
                'date_from' => $query->whereDate($field, '>=', $value),
                'date_to' => $query->whereDate($field, '<=', $value),
                'in' => $query->whereIn($field, is_array($value) ? $value : explode(',', $value)),
                default => $query->where($field, $value),
            };
        }

        return $query;
    }

    public function scopeSort(Builder $query, Request $request, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $sort = $request->input('sort', $defaultSort);
        $direction = $request->input('direction', $defaultDirection);

        if (in_array($sort, $this->getSortableFields())) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        }

        return $query;
    }

    protected function getFilterableFields(): array
    {
        return $this->filterable ?? [];
    }

    protected function getSortableFields(): array
    {
        return $this->sortable ?? ['created_at', 'updated_at'];
    }
}
