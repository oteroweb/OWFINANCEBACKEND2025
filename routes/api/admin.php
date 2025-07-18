<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\RoleCheckController;

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
