<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\Client;
use App\Models\Entities\Repositories\ClientRepository;

class ClientController extends Controller
{
    protected $repo;

    public function __construct(ClientRepository $repo)
    {
        $this->repo = $repo;
    }

    public function all(Request $request)
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
        'data' => $this->repo->all($request->only(['page','per_page','sort_by','descending','search']))
        ]);
    }
    public function allActive(Request $request)
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
        'data' => $this->repo->allActive($request->only(['page','per_page','sort_by','descending','search']))
        ]);
    }
    public function withTrashed()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $this->repo->withTrashed()
        ]);
    }
    public function find($id)
    {
        $client = $this->repo->find($id);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $client
        ]);
    }
    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable',
        ]);
    $data['active'] = $request->boolean('active', true);
        $client = $this->repo->create($data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $client
        ]);
    }
    public function update(Request $request, $id)
    {
        $client = $this->repo->find($id);
        $data = $request->only(['name', 'email', 'phone']);
        if ($request->has('active')) {
            $data['active'] = $request->boolean('active');
        }
        $client = $this->repo->update($client, $data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $client
        ]);
    }
    public function delete($id)
    {
        $client = $this->repo->find($id);
        $client = $this->repo->delete($client);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $client
        ]);
    }
    public function change_status($id)
    {
        $client = $this->repo->find($id);
        $client = $this->repo->changeStatus($client);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Status Client updated'),
            'data' => $client
        ]);
    }
}
