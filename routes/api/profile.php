<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

// Rutas de perfil bajo /api/v1/user/profile
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user/profile', [UserController::class, 'profile']);
    Route::match(['put', 'patch'], '/user/profile', [UserController::class, 'updateProfile']);
    // Solo administradores pueden actualizar por ID (el controlador valida el rol)
    Route::match(['put', 'patch'], '/user/profile/{id}', [UserController::class, 'updateProfile'])->whereNumber('id');
});
