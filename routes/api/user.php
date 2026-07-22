<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\UserSettingController;
use App\Http\Controllers\Api\UserSecurityController;
use App\Http\Controllers\UserFinancialProfileController;

// Rutas para el usuario autenticado (Self context) bajo /api/v1/user
Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // Perfil
    Route::get('/profile', [UserController::class, 'profile']);
    Route::match(['put', 'patch'], '/profile', [UserController::class, 'updateProfile']);

    // Configuración y Preferencias
    Route::get('/settings', [UserSettingController::class, 'show']);
    Route::match(['put', 'patch'], '/settings', [UserSettingController::class, 'update']);

    // Perfil financiero (onboarding + settings)
    Route::get('/financial-profile', [UserFinancialProfileController::class, 'show']);
    Route::put('/financial-profile', [UserFinancialProfileController::class, 'update']);
    Route::put('/financial-profile/jar-descriptions', [UserFinancialProfileController::class, 'updateJarDescriptions']);

    // Seguridad — PIN de acceso rápido (OWF-206). OWF-293: controller y tests ya
    // existían desde OWF-206, pero las rutas nunca se registraron — 404 en los 6
    // tests de UserSecurityPinTest.
    Route::get('/security/pin-status', [UserSecurityController::class, 'pinStatus']);
    Route::put('/security/pin', [UserSecurityController::class, 'setPin']);
    Route::post('/security/pin/verify', [UserSecurityController::class, 'verifyPin']);
    Route::delete('/security/pin', [UserSecurityController::class, 'removePin']);
});

// Rutas administrativas que actúan sobre un ID de usuario específico (todavía bajo /api/v1/user/profile/{id})
Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {
    // Solo administradores pueden actualizar por ID (el controlador valida el rol internamente)
    Route::match(['put', 'patch'], '/profile/{id}', [UserController::class, 'updateProfile'])->whereNumber('id');
});
