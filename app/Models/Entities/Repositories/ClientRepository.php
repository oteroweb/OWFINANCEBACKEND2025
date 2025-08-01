<?php

namespace App\Models\Entities\Repositories;

use App\Models\Entities\Client;

class ClientRepository
{
    public function all()
    {
        return Client::all();
    }
    public function allActive()
    {
        return Client::where('active', 1)->get();
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
        $client->delete();
        return true;
    }
    public function changeStatus(Client $client)
    {
        $client->active = !$client->active;
        $client->save();
        return $client;
    }
}
