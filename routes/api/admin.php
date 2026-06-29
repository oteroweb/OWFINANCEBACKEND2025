<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\RoleCheckController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\TransactionTypeController;
use App\Http\Controllers\Admin\AiMonitorController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserAdminController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'])->get('/check-role', [RoleCheckController::class, 'check']);

// Ejemplo de ruta protegida solo para administradores
Route::middleware(['auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'])->get('/admin-only', [AuthController::class, 'adminOnly']);

// Ejemplo de ruta protegida solo para usuarios
Route::middleware(['auth:sanctum', 'App\\Http\\Middleware\\CheckRole:user'])->get('/user-only', [AuthController::class, 'userOnly']);

// Ejemplo de ruta protegida solo para invitados
Route::middleware(['auth:sanctum', 'App\\Http\\Middleware\\CheckRole:guest'])->get('/guest-only', [AuthController::class, 'guestOnly']);

// Admin panel routes
Route::middleware(['auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Transaction Types CRUD
    Route::get('/transaction-types', [TransactionTypeController::class, 'index']);
    Route::post('/transaction-types', [TransactionTypeController::class, 'store']);
    Route::put('/transaction-types/{id}', [TransactionTypeController::class, 'update']);
    Route::delete('/transaction-types/{id}', [TransactionTypeController::class, 'destroy']);

    // AI Monitor
    Route::get('/ai/providers', [AiMonitorController::class, 'providers']);
    Route::get('/ai/stats',     [AiMonitorController::class, 'stats']);

    // Roles CRUD
    Route::get('/roles',         [RoleController::class, 'index']);
    Route::post('/roles',        [RoleController::class, 'store']);
    Route::put('/roles/{id}',    [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // System health
    Route::get('/system', [SystemController::class, 'index']);

    // Admin User Management (OWF-140..144)
    Route::get('/users/{id}/detail',                [UserAdminController::class, 'detail']);
    Route::post('/users/{id}/impersonate',          [UserAdminController::class, 'impersonate']);
    Route::put('/users/{id}/password',              [UserAdminController::class, 'changePassword']);
    Route::delete('/users/{id}/tokens',             [UserAdminController::class, 'revokeTokens']);
    Route::post('/users/{id}/reset-password-email', [UserAdminController::class, 'sendResetEmail']);
});
