<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoleCheckController extends Controller
{
    /**
     * Verifica acceso solo para administradores
     *
     * @group Admin
     * @authenticated
     * @response 200 {"message": "Acceso permitido solo para administradores."}
     * @response 403 {"message": "No autorizado."}
     */
    public function check(Request $request)
    {
        return response()->json(['message' => 'Acceso permitido solo para administradores.']);
    }
}
