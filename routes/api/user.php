<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\UserSettingController;
use App\Http\Controllers\UserFinancialProfileController;

// Rutas para el usuario autenticado (Self context) bajo /api/v1/user
Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // Perfil
    Route::get('/profile', [UserController::class, 'profile']);
    Route::match(['put', 'patch'], '/profile', [UserController::class, 'updateProfile']);

    // Configuración y Preferencias
    Route::get('/settings', [UserSettingController::class, 'show']);
    Route::put('/settings', [UserSettingController::class, 'update']);

    // Perfil financiero (onboarding + settings)
    Route::get('/financial-profile', [UserFinancialProfileController::class, 'show']);
    Route::put('/financial-profile', [UserFinancialProfileController::class, 'update']);
    Route::put('/financial-profile/jar-descriptions', [UserFinancialProfileController::class, 'updateJarDescriptions']);
});

// Rutas administrativas que actúan sobre un ID de usuario específico (todavía bajo /api/v1/user/profile/{id})
Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // Solo administradores pueden actualizar por ID (el controlador valida el rol internamente)
    Route::match(['put', 'patch'], '/profile/{id}', [UserController::class, 'updateProfile'])->whereNumber('id');
});
