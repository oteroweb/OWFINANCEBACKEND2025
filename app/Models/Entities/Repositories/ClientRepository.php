<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\Client;

class ClientRepository
{
    public function all(array $params = [])
    {
        $query = Client::whereIn('active', [1,0]);
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
        $query = Client::where('active', 1);
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
    public function withTrashed()
    {
        return Client::withTrashed()->get();
    }
    public function find($id)
    {
        return Client::findOrFail($id);
    }
    public function create(array $data)
    {
        return Client::create($data);
    }
    public function update(Client $client, array $data)
    {
        $client->update($data);
        return $client;
    }
    public function delete(Client $client)
    {
        $client->active = 0;
        $client->save();
        $client->delete();
        return $client;
    }
    public function changeStatus(Client $client)
    {
        $client->active = !$client->active;
        $client->save();
        return $client;
    }
}
