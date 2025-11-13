<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Repositories\UserRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Entities\UserCurrency;
use App\Models\Repositories\AccountRepo;

class UserController extends Controller
{
    public function __construct(private UserRepo $repo) {}

    /**
     * Obtener el perfil del usuario autenticado
     */
    public function profile(Request $request)
    {
        // Incluir cuentas con su moneda y tasas del usuario (históricas y actuales)
        $user = $request->user()->load([
            'client', 'role', 'currency',
            'accounts.currency', // cuentas con su moneda
            'currencyRates.currency', // historial de tasas por usuario
            'currentCurrencyRates.currency', // tasas actuales por usuario
        ]);

        // Recalcular balances de todas las cuentas del usuario y adjuntar al payload
        try {
            $repo = app(AccountRepo::class);
            foreach ($user->accounts as $acc) {
                $newBalance = $repo->recalcAndStoreFromInitialByType((int)$acc->id);
                $acc->balance = $newBalance;
                $acc->setAttribute('balance_cached', $newBalance);
            }
        } catch (\Throwable $e) { /* mantener respuesta aunque falle el recálculo */ }

        // Construir arreglo de tasas: para cada moneda presente en las cuentas del usuario
        // tomar la última tasa marcada como is_current=true; si no existe, usar 1.0
        // Importante: excluir la moneda base (moneda por defecto del usuario) para que las tasas sean pares BASE/OTRA
        $baseCurrencyId = (int)($user->currency_id ?? 0);
        $accountCurrencyIds = $user->accounts
            ->pluck('currency_id')
            ->filter()
            ->unique()
            ->reject(fn ($cid) => (int)$cid === $baseCurrencyId)
            ->values();
        $rates = [];
        foreach ($accountCurrencyIds as $cid) {
            // 1) Preferir la última marcada como current
            $rec = UserCurrency::with('currency')
                ->where('user_id', $user->id)
                ->where('currency_id', $cid)
                ->where('is_current', true)
                ->orderByDesc('updated_at')
                ->first();
            // 2) Si no hay current, tomar la última registrada para esa moneda
            if (!$rec) {
                $rec = UserCurrency::with('currency')
                    ->where('user_id', $user->id)
                    ->where('currency_id', $cid)
                    ->orderByDesc('updated_at')
                    ->first();
            }
            if ($rec) {
                $rates[] = [
                    'id' => $rec->id,
                    'currency' => $rec->currency,
                    'current_rate' => (float)($rec->current_rate ?? 1.0),
                    'is_official' => (bool)($rec->is_official ?? false),
                    'is_current' => (bool)($rec->is_current ?? false),
                    'updated_at' => $rec->updated_at,
                ];
            } else {
                // Fallback: tasa 1.0 sin marcar; tomar objeto currency desde cualquier cuenta con ese currency_id
                $accWithCurrency = $user->accounts->firstWhere('currency_id', (int)$cid);
                $rates[] = [
                    'id' => null,
                    'currency' => $accWithCurrency?->currency,
                    'current_rate' => 1.0,
                    'is_official' => false,
                    'is_current' => false,
                    'updated_at' => null,
                ];
            }
        }

        $payload = $user->toArray();
        // Adjuntar metadata de moneda base para claridad del cliente
        $payload['base_currency_id'] = $baseCurrencyId ?: null;
        $payload['base_currency'] = $user->currency ?? null;
        $payload['rates'] = $rates;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $payload
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
                // Para self-profile, incluir también cuentas con moneda y tasas
                $user = $authUser->load([
                    'client', 'role', 'currency',
                    'accounts.currency',
                    'currencyRates.currency',
                    'currentCurrencyRates.currency',
                ]);
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
        // Devolver con cuentas/monedas y tasas incluidas + arreglo reducido de tasas actuales
        $updated->load([
            'client', 'role', 'currency',
            'accounts.currency',
            'currencyRates.currency',
            'currentCurrencyRates.currency',
        ]);

        // Recalcular balances de las cuentas
        try {
            $repo = app(AccountRepo::class);
            foreach ($updated->accounts as $acc) {
                $newBalance = $repo->recalcAndStoreFromInitialByType((int)$acc->id);
                $acc->balance = $newBalance;
                $acc->setAttribute('balance_cached', $newBalance);
            }
        } catch (\Throwable $e) { }

        // Tasas por cada moneda de sus cuentas (última is_current; si no, 1.0), excluyendo la moneda base
        $baseCurrencyId = (int)($updated->currency_id ?? 0);
        $accountCurrencyIds = $updated->accounts
            ->pluck('currency_id')
            ->filter()
            ->unique()
            ->reject(fn ($cid) => (int)$cid === $baseCurrencyId)
            ->values();
        $rates = [];
        foreach ($accountCurrencyIds as $cid) {
            $rec = UserCurrency::with('currency')
                ->where('user_id', $updated->id)
                ->where('currency_id', $cid)
                ->where('is_current', true)
                ->orderByDesc('updated_at')
                ->first();
            if (!$rec) {
                $rec = UserCurrency::with('currency')
                    ->where('user_id', $updated->id)
                    ->where('currency_id', $cid)
                    ->orderByDesc('updated_at')
                    ->first();
            }
            if ($rec) {
                $rates[] = [
                    'id' => $rec->id,
                    'currency' => $rec->currency,
                    'current_rate' => (float)($rec->current_rate ?? 1.0),
                    'is_official' => (bool)($rec->is_official ?? false),
                    'is_current' => (bool)($rec->is_current ?? false),
                    'updated_at' => $rec->updated_at,
                ];
            } else {
                $accWithCurrency = $updated->accounts->firstWhere('currency_id', (int)$cid);
                $rates[] = [
                    'id' => null,
                    'currency' => $accWithCurrency?->currency,
                    'current_rate' => 1.0,
                    'is_official' => false,
                    'is_current' => false,
                    'updated_at' => null,
                ];
            }
        }

        $payload = $updated->toArray();
        $payload['base_currency_id'] = $baseCurrencyId ?: null;
        $payload['base_currency'] = $updated->currency ?? null;
        $payload['rates'] = $rates;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Perfil actualizado correctamente.'),
            'data' => $payload,
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
