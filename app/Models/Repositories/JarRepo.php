<?php

namespace App\Models\Repositories;

use App\Models\Entities\Jar;
use Illuminate\Support\Str;

class JarRepo
{
    public function all(array $params = [])
    {
        $query = Jar::with(['user','categories','baseCategories'])
            ->whereIn('active', [0,1]);

    // #todo(admin): If/when admin roles exist, allow overriding user_id scoping here.

        // Filters
        if (array_key_exists('active', $params) && $params['active'] !== null && $params['active'] !== '') {
            $query->where('active', (int) filter_var($params['active'], FILTER_VALIDATE_BOOLEAN));
        }
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        if (!empty($params['search'])) {
            $this->applyGlobalSearch($query, $params['search'], ['name','user.name']);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'sort_order';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');

        // Pagination
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            return $query->paginate($perPage);
        }
        return $query->get();
    }

    public function allActive(array $params = [])
    {
        $params['active'] = true;
        return $this->all($params);
    }

    public function find($id)
    {
        return Jar::find($id);
    }

    public function store(array $data)
    {
        return Jar::create($data);
    }

    public function update(Jar $jar, array $data)
    {
        $jar->update($data);
        return $jar;
    }

    public function delete(Jar $jar)
    {
        // mirror soft-delete pattern
        $jar->active = 0;
        $jar->save();
        return $jar->delete();
    }

    public function withTrashed()
    {
    // #todo(scope): Consider accepting $params to filter by user_id like all(), for consistency.
    return Jar::withTrashed()->with(['user','categories','baseCategories'])->get();
    }

    public function sumPercentForUser(int $userId, ?int $excludeJarId = null): float
    {
        $q = Jar::where('user_id', $userId)
            ->where('type', 'percent')
            ->where('active', 1);
        if ($excludeJarId) {
            $q->where('id', '!=', $excludeJarId);
        }
        return (float) $q->sum('percent');
    }

    private function applyGlobalSearch($query, string $searchTerm, array $fields): void
    {
        $query->where(function ($q) use ($searchTerm, $fields) {
            foreach ($fields as $field) {
                if (strpos($field, '.') !== false) {
                    [$relation, $column] = explode('.', $field, 2);
                    $relation = Str::camel($relation);
                    $q->orWhereHas($relation, function ($q2) use ($column, $searchTerm) {
                        $q2->where($column, 'like', "%{$searchTerm}%");
                    });
                } else {
                    $q->orWhere($field, 'like', "%{$searchTerm}%");
                }
            }
        });
    }
}
