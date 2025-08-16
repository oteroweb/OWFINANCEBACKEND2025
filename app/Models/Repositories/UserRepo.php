<?php

namespace App\Models\Repositories;

use App\Models\User;

class UserRepo
{
    public function all(array $params = [])
    {
        $query = User::whereIn('active', [1, 0])
            ->with(['client', 'role', 'currency']);

        if (!empty($params['client_id'])) {
            $query->where('client_id', $params['client_id']);
        }

        if (!empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
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

    public function allActive(array $params = [])
    {
        $query = User::where('active', 1)
            ->with(['client']);

        if (!empty($params['client_id'])) {
            $query->where('client_id', $params['client_id']);
        }

        if (!empty($params['search'])) {
            $term = $params['search'];
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%");
            });
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

    public function find($id)
    {
        return User::with(['client'])->findOrFail($id);
    }

    public function store(array $data)
    {
        $user = new User();
        $user->fill($data);
        $user->save();
    return $user->load('client');
    }

    public function update(User $user, array $data)
    {
        $user->fill($data);
        $user->save();
    return $user->load('client');
    }

    public function delete(User $user)
    {
        $user->active = 0;
        $user->save();
        $user->delete();
        return $user;
    }

    public function withTrashed()
    {
        return User::withTrashed()->get();
    }

    public function changeStatus(User $user)
    {
        $user->active = !$user->active;
        $user->save();
    return $user->load('client');
    }
}
