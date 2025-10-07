<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(private UserRepo $repo) {}

    /**
     * Obtener el perfil del usuario autenticado
     */
    public function profile(Request $request)
    {
    // Include the user's currencies (user-specific rates) with base currency details
    $user = $request->user()->load(['client', 'role', 'currency', 'accounts', 'currencyRates.currency', 'currencies']);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $user
        ]);
    }

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

    /**
     * Actualizar el perfil del usuario autenticado. Si es admin, puede actualizar a cualquier usuario mediante ID.
     */
    public function updateProfile(Request $request, $id = null)
    {
        $authUser = $request->user();

        // Determinar usuario a actualizar
        $targetId = $id ?? $request->input('id');
        if ($targetId) {
            // Solo admin puede actualizar a otros
            if (!$authUser->isAdmin() && (int)$targetId !== (int)$authUser->id) {
                return response()->json([
                    'status' => 'ERROR',
                    'code' => 403,
                    'message' => __('No autorizado para actualizar este usuario.'),
                    'data' => null,
                ], 403);
            }
            $user = $this->repo->find($targetId);
        } else {
            // For self-profile, include currencies as well
            $user = $authUser->load(['client', 'role', 'currency', 'accounts', 'currencyRates.currency', 'currencies']);
        }

        // Campos permitidos
        $adminOnlyFields = ['role_id', 'active', 'client_id', 'balance'];
        $commonFields = ['name', 'phone', 'email', 'password', 'currency_id'];
        $allowed = $authUser->isAdmin() ? array_merge($commonFields, $adminOnlyFields) : $commonFields;

        // Reglas de validación dinámicas
        $rules = [
            'name' => 'sometimes|string',
            'phone' => 'sometimes|nullable|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'currency_id' => 'sometimes|nullable|exists:currencies,id',
        ];
        if ($authUser->isAdmin()) {
            $rules = array_merge($rules, [
                'role_id' => 'sometimes|exists:roles,id',
                'active' => 'sometimes|boolean',
                'client_id' => 'sometimes|nullable|exists:clients,id',
                'balance' => 'sometimes|numeric',
            ]);
        }

        $data = $request->only($allowed);
        $validated = validator($data, $rules)->validate();

        if (array_key_exists('active', $validated)) {
            $validated['active'] = (bool)$request->boolean('active');
        }
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $updated = $this->repo->update($user, $validated);
    // Return with currencies included
    $updated->load(['client', 'role', 'currency', 'accounts', 'currencyRates.currency', 'currencies']);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Perfil actualizado correctamente.'),
            'data' => $updated,
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
