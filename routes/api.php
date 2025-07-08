<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Obtener perfil del usuario autenticado
    Route::get('/profile', [AuthController::class, 'profile']);
    
    // Cerrar sesión
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Ruta de ejemplo (puedes eliminarla si no la necesitas)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
