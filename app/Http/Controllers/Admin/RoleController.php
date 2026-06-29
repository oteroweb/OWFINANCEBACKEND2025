<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'data'   => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:roles,slug',
        ]);

        $role = Role::create($data);

        return response()->json(['status' => 'OK', 'code' => 201, 'data' => $role], 201);
    }

    public function update(Request $request, int $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'slug' => 'sometimes|string|max:100|unique:roles,slug,' . $id,
        ]);

        $role->update($data);

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $role]);
    }

    public function destroy(int $id)
    {
        Role::findOrFail($id)->delete();

        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Role deleted']);
    }
}
