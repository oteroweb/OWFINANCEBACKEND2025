<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Iniciar sesión y crear token
     *
     * @group Autenticación
     * @bodyParam email string required El email del usuario. Ejemplo: admin@demo.com
     * @bodyParam password string required La contraseña del usuario. Ejemplo: password
     * @bodyParam device_name string required Nombre del dispositivo. Ejemplo: web
     * @response 200 {
     *   "token": "...",
     *   "user": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@demo.com",
     *     "role": {
     *       "id": 1,
     *       "name": "Admin",
     *       "slug": "admin"
     *     }
     *   }
     * }
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken($request->device_name)->plainTextToken;
        // Eager-load relations for client bootstrap
        $user->load(['role', 'currency', 'accounts.currency', 'currencyRates.currency', 'currencies']);

        // Default currencies array (from user's accounts). If none configured or nulls, fallback to [1].
        $accountCurrencyIds = collect($user->accounts ?? [])->pluck('currency_id')
            ->filter(fn($v) => !is_null($v))
            ->unique()->values()->all();
        $defaultCurrencyIds = !empty($accountCurrencyIds) ? $accountCurrencyIds : [1];

        // Build user.rates array: [{ name: <currency code lower>, value: <current_rate> }]
        $rates = collect($user->currencyRates ?? [])
            ->filter(fn($r) => (bool)($r->is_current ?? false))
            ->sortByDesc(fn($r) => $r->updated_at ?? $r->created_at)
            ->groupBy('currency_id')
            ->map(fn($grp) => $grp->first())
            ->values()
            ->map(function($r){
                $code = strtolower((string)($r->currency->code ?? ''));
                return [ 'name' => $code, 'value' => (float)($r->current_rate ?? 0.0) ];
            })
            ->all();
        // Attach as dynamic attribute so it's serialized within user
        $user->setAttribute('rates', $rates);

        return response()->json([
            'token' => $token,
            'user' => $user,
            'default_currency_ids' => $defaultCurrencyIds,
        ]);
    }

    /**
     * Cerrar sesión (eliminar tokens)
     *
     * @group Autenticación
     * @authenticated
     * @response 200 {"message": "Sesión cerrada correctamente"}
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Obtener el perfil del usuario autenticado
     *
     * @group Autenticación
     * @authenticated
     * @header Authorization string required Bearer token obtenido en el login. Ejemplo: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
     * @response 200 {
     *   "user": {
     *     "id": 1,
     *     "name": "Admin",
     *     "email": "admin@demo.com",
     *     "role": {
     *       "id": 1,
     *       "name": "Admin",
     *       "slug": "admin"
     *     }
     *   }
     * }
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['currency', 'currencyRates.currency']);
        $rates = collect($user->currencyRates ?? [])
            ->filter(fn($r) => (bool)($r->is_current ?? false))
            ->sortByDesc(fn($r) => $r->updated_at ?? $r->created_at)
            ->groupBy('currency_id')
            ->map(fn($grp) => $grp->first())
            ->values()
            ->map(function($r){
                $code = strtolower((string)($r->currency->code ?? ''));
                return [ 'name' => $code, 'value' => (float)($r->current_rate ?? 0.0) ];
            })
            ->all();
        $user->setAttribute('rates', $rates);

        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Verifica acceso solo para administradores
     *
     * @group Roles
     * @authenticated
     * @header Authorization string required Bearer token obtenido en el login. Ejemplo: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
     * @response 200 {"message": "Solo administradores pueden acceder."}
     * @response 403 {"message": "No autorizado."}
     */
    public function adminOnly(Request $request)
    {
        return response()->json(['message' => 'Solo administradores pueden acceder.']);
    }

    /**
     * Verifica acceso solo para usuarios
     *
     * @group Roles
     * @authenticated
     * @header Authorization string required Bearer token obtenido en el login. Ejemplo: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
     * @response 200 {"message": "Solo usuarios pueden acceder."}
     * @response 403 {"message": "No autorizado."}
     */
    public function userOnly(Request $request)
    {
        return response()->json(['message' => 'Solo usuarios pueden acceder.']);
    }

    /**
     * Verifica acceso solo para invitados
     *
     * @group Roles
     * @authenticated
     * @header Authorization string required Bearer token obtenido en el login. Ejemplo: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
     * @response 200 {"message": "Solo invitados pueden acceder."}
     * @response 403 {"message": "No autorizado."}
     */
    public function guestOnly(Request $request)
    {
        return response()->json(['message' => 'Solo invitados pueden acceder.']);
    }
}
