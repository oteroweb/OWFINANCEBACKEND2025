<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function all()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => User::all()
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['active'] = 1;
        $user = User::create($data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function find($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->only(['name', 'email', 'password']);
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => null
        ]);
    }

    public function allActive()
    {
        $users = User::where('active', 1)->get();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $users
        ]);
    }

    public function withTrashed()
    {
        $users = User::withTrashed()->get();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $users
        ]);
    }

    public function change_status($id)
    {
        $user = User::findOrFail($id);
        $user->active = !$user->active;
        $user->save();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Status User updated'),
            'data' => $user
        ]);
    }
}
