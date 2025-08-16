<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private UserRepo $repo) {}

    public function all(Request $request)
    {
        $params = $request->only(['page','per_page','sort_by','descending','search','client_id']);
        $users = $this->repo->all($params);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $users
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'client_id' => 'nullable|exists:clients,id',
            'active' => 'sometimes|boolean',
            'currency_id' => 'nullable|exists:currencies,id',
        ]);
    $data['password'] = Hash::make($data['password']);
        $data['active'] = $request->boolean('active', true);
        $user = $this->repo->store($data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function find($id)
    {
    $user = $this->repo->find($id);
    return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->repo->find($id);
        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string',
            'client_id' => 'sometimes|nullable|exists:clients,id',
            'active' => 'sometimes|boolean',
        ]);
        if ($request->exists('active')) {
            $data['active'] = $request->boolean('active');
        }
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user = $this->repo->update($user, $data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function delete($id)
    {
    $user = $this->repo->find($id);
    $user = $this->repo->delete($user);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function allActive(Request $request)
    {
        $params = $request->only(['page','per_page','sort_by','descending','search','client_id']);
        $users = $this->repo->allActive($params);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $users
        ]);
    }

    public function withTrashed()
    {
    $users = $this->repo->withTrashed();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $users
        ]);
    }

    public function change_status($id)
    {
    $user = $this->repo->find($id);
    $user = $this->repo->changeStatus($user);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Status User updated'),
            'data' => $user
        ]);
    }
}
