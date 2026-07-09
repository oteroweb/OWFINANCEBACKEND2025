<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserSecurityController extends Controller
{
    /**
     * Indica si el usuario autenticado ya tiene un PIN configurado.
     */
    public function pinStatus(Request $request)
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => ['has_pin' => (bool) $request->user()->security_pin],
        ], 200);
    }

    /**
     * Crea o reemplaza el PIN de seguridad. Requiere la contraseña actual
     * para confirmar identidad (mismo criterio que cambiar contraseña).
     */
    public function setPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => ['required', 'string', 'regex:/^[0-9]{4,6}$/'],
            'password' => ['required', 'string'],
        ], [
            'pin.regex' => __('El PIN debe tener entre 4 y 6 dígitos.'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Datos inválidos'),
                'data' => $validator->errors()->getMessages(),
            ], 400);
        }

        $user = $request->user();
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => __('Contraseña incorrecta'),
            ], 422);
        }

        $user->security_pin = $request->input('pin');
        $user->save();

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PIN configurado correctamente'),
        ], 200);
    }

    /**
     * Verifica el PIN ingresado contra el hash almacenado.
     */
    public function verifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pin' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Datos inválidos'),
            ], 400);
        }

        $user = $request->user();
        if (!$user->security_pin) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 404,
                'message' => __('No hay PIN configurado'),
            ], 404);
        }

        $valid = Hash::check($request->input('pin'), $user->security_pin);

        return response()->json([
            'status' => $valid ? 'OK' : 'FAILED',
            'code' => $valid ? 200 : 422,
            'data' => ['valid' => $valid],
        ], $valid ? 200 : 422);
    }

    /**
     * Elimina el PIN configurado. Requiere contraseña actual.
     */
    public function removePin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Datos inválidos'),
            ], 400);
        }

        $user = $request->user();
        if (!Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 422,
                'message' => __('Contraseña incorrecta'),
            ], 422);
        }

        $user->security_pin = null;
        $user->save();

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('PIN eliminado'),
        ], 200);
    }
}
