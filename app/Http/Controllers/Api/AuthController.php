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
        $user->load('role');
        $user->load('currency'); // Cargar la relación de moneda si es necesario
        return response()->json([
            'token' => $token,
            'user' => $user,
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
        return response()->json([
            'user' => $request->user()
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
