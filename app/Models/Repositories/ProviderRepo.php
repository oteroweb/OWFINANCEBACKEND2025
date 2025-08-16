<?php

namespace App\Models\Repositories;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Entities\Provider;

class ProviderRepo {
    public function all(array $params = []) {
        $query = Provider::whereIn('active', [1, 0])
            ->with(['user']);

        // Relation filters
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        } elseif (!empty($params['user'])) {
            $needle = $params['user'];
            $query->whereHas('user', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        // Global search across fields + relation
        if (!empty($params['search'])) {
            $this->applyGlobalSearch($query, $params['search'], [
                'name', 'address', 'email', 'phone', 'website', 'country', 'city', 'postal_code', 'state', 'description',
                'user.name', 'user.email',
            ]);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'name';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');

        // Pagination
        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function allActive(array $params = []) {
        $query = Provider::where('active', 1)
            ->with(['user']);

        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        } elseif (!empty($params['user'])) {
            $needle = $params['user'];
            $query->whereHas('user', function ($q) use ($needle) {
                $q->where('name', 'like', "%{$needle}%")
                  ->orWhere('email', 'like', "%{$needle}%");
            });
        }

        if (!empty($params['search'])) {
            $this->applyGlobalSearch($query, $params['search'], [
                'name', 'address', 'email', 'phone', 'website', 'country', 'city', 'postal_code', 'state', 'description',
                'user.name', 'user.email',
            ]);
        }

        $sortBy = $params['sort_by'] ?? 'name';
        $descending = filter_var($params['descending'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $query->orderBy($sortBy, $descending ? 'desc' : 'asc');

        if (!empty($params['page'])) {
            $perPage = $params['per_page'] ?? 15;
            return $query->paginate($perPage);
        }

        return $query->get();
    }

    public function find($id) {
        $provider = Provider::with(['user'])->find($id);
        return $provider;
    }
    public function store($data) {
        $provider = new Provider();
        $provider->fill($data);
        $provider->save();
        return $provider;
    }
    public function update($provider, $data) {
        $provider->fill($data);
        $provider->save();
        return $provider;
    }
    public function delete($provider) {
        $provider->active = 0;
        $provider->save();
        $provider->delete();
        return $provider;
    }
    public function withTrashed() {
        $provider = Provider::withTrashed()->with(['user'])->get();
        return $provider;
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
