<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\Client;

class ClientController extends Controller
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
    public function save(Request $request)
    {
        $client = Client::create($request->all());
        return response()->json($client, 201);
    }
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        $client->update($request->all());
        return response()->json($client);
    }
    public function delete($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json(null, 204);
    }
    public function change_status($id)
    {
        $client = Client::findOrFail($id);
        $client->active = !$client->active;
        $client->save();
        return response()->json($client);
    }
}
