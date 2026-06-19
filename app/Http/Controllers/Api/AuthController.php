<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Role;
use App\Models\Entities\Account;
use App\Models\Entities\AccountType;
use App\Models\Entities\Currency;

class AuthController extends Controller
{
    /**
     * Login and return Sanctum token plus enriched user payload
     * (accounts with currency and current rates per currency).
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 401,
                'message' => 'Credenciales inválidas',
            ], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        // Load related data and build compact rates array (current per currency, prefer official)
        $user->load([
            'client', 'role', 'currency',
            'accounts.currency',
            'currencyRates.currency',
            'currentCurrencyRates.currency',
        ]);
        // Tasas por cada moneda de sus cuentas (última is_current; si no, la más reciente), excluyendo la moneda base
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
            $rec = $user->currencyRates()
                ->where('currency_id', $cid)
                ->where('is_current', true)
                ->orderByDesc('updated_at')
                ->first();

            // 2) Si no hay current, tomar la última registrada para esa moneda
            if (!$rec) {
                $rec = $user->currencyRates()
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
            }
        }


    $payload = $user->toArray();
    $payload['rates'] = $rates;
    // Compat: exponer role_slug/role_name dentro de data
    $payload['role_slug'] = $user->role->slug ?? null;
    $payload['role_name'] = $user->role->name ?? null;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Autenticado correctamente',
            'token' => $token,
            // Compat: top-level role string para clientes que esperan auth.role
            'role' => $user->role->slug ?? null,
            'data' => $payload,
        ]);
    }

    /**
     * Register a new user account
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            'currency_id'           => 'nullable|integer|exists:currencies,id',
        ]);

        // Assign default 'user' role
        $userRole = Role::where('slug', 'user')->first();
        if (!$userRole) {
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => 'Rol de usuario no configurado en el sistema.',
            ], 500);
        }

        $user = User::create([
            'name'        => $data['name'],
            'email'       => $data['email'],
            'password'    => Hash::make($data['password']),
            'role_id'     => $userRole->id,
            'currency_id' => $data['currency_id'] ?? null,
            'active'      => true,
        ]);

        $token = $user->createToken('api')->plainTextToken;

        // Auto-create default "Billetera" account for new users (Lite implicit wallet)
        $this->createDefaultAccount($user);

        $user->load(['role', 'currency']);

        $payload = $user->toArray();
        $payload['role_slug'] = $user->role->slug ?? null;
        $payload['role_name'] = $user->role->name ?? null;
        $payload['rates']     = [];

        return response()->json([
            'status'  => 'OK',
            'code'    => 201,
            'message' => 'Cuenta creada exitosamente.',
            'token'   => $token,
            'role'    => $user->role->slug ?? null,
            'data'    => $payload,
        ], 201);
    }

    /**
     * Logout current token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Sesión cerrada correctamente']);
    }

    /**
     * Auto-create a default "Billetera" (Efectivo) account for a newly registered user.
     * Silently skips on any error — account creation is best-effort.
     */
    private function createDefaultAccount(User $user): void
    {
        try {
            $accountType = AccountType::where('name', 'Efectivo')->first()
                        ?? AccountType::first();
            if (!$accountType) return;

            $currencyId = $user->currency_id
                       ?? Currency::where('code', 'USD')->value('id')
                       ?? Currency::first()?->id;
            if (!$currencyId) return;

            $account = Account::create([
                'name'            => 'Billetera',
                'currency_id'     => $currencyId,
                'account_type_id' => $accountType->id,
                'initial'         => 0,
                'active'          => true,
            ]);

            $account->users()->attach($user->id, ['is_owner' => 1, 'sort_order' => 0]);
        } catch (\Throwable) {
            // Non-critical — user can create accounts manually
        }
    }
}
