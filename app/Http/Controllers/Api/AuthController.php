<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
                'message' => __('Invalid credentials'),
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
        Log::info('User loaded with relationships:', $user->toArray());

        // Tasas por cada moneda de sus cuentas (última is_current; si no, la más reciente), excluyendo la moneda base
        $baseCurrencyId = (int)($user->currency_id ?? 0);
        $accountCurrencyIds = $user->accounts
            ->pluck('currency_id')
            ->filter()
            ->unique()
            ->reject(fn ($cid) => (int)$cid === $baseCurrencyId)
            ->values();
        Log::info('Account currency IDs for rates processing:', $accountCurrencyIds->toArray());
        $rates = [];
        foreach ($accountCurrencyIds as $cid) {
            Log::info("Processing currency ID: {$cid}");
            // 1) Preferir la última marcada como current
            $rec = $user->currencyRates()
                ->where('currency_id', $cid)
                ->where('is_current', true)
                ->orderByDesc('updated_at')
                ->first();
            Log::info("Found current rate record for currency {$cid}:", $rec ? $rec->toArray() : null);

            // 2) Si no hay current, tomar la última registrada para esa moneda
            if (!$rec) {
                Log::info("No current rate for currency ID: {$cid}. Falling back to latest rate.");
                $rec = $user->currencyRates()
                    ->where('currency_id', $cid)
                    ->orderByDesc('updated_at')
                    ->first();
                Log::info("Found latest rate record for currency {$cid}:", $rec ? $rec->toArray() : null);
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
        Log::info('Final rates array:', $rates);

    $payload = $user->toArray();
    $payload['rates'] = $rates;
    // Compat: exponer role_slug/role_name dentro de data
    $payload['role_slug'] = $user->role->slug ?? null;
    $payload['role_name'] = $user->role->name ?? null;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Authenticated'),
            'token' => $token,
            // Compat: top-level role string para clientes que esperan auth.role
            'role' => $user->role->slug ?? null,
            'data' => $payload,
        ]);
    }

    /**
     * Logout current token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Sesión cerrada correctamente']);
    }
}
