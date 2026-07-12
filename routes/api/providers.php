<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\ProviderController;

  // IMPORTANTE: /all (admin) debe registrarse ANTES que /{id} (no-admin) — mismo método GET,
  // "all" matchearía el wildcard {id} si /{id} se registra primero.
  Route::group([
    'middleware' => ['api', 'auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'],
    'prefix'     => 'providers',
  ], function () {
    Route::get('/all', [ProviderController::class, 'withTrashed']);
    Route::patch('/{id}/status', [ProviderController::class, 'change_status']);
    Route::put('/{id}', [ProviderController::class, 'update']);
    Route::delete('/{id}', [ProviderController::class, 'delete']);
  });

  // Lectura + creación: cualquier usuario autenticado (scoped a sus propios providers +
  // globales en el controller/repo — OWF-264). Gestión arriba sigue admin-only.
  Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'providers',
  ], function () {
    Route::post('/', [ProviderController::class, 'save']);
    Route::get('/active', [ProviderController::class, 'allActive']);
    Route::get('/', [ProviderController::class, 'all']);
    Route::get('/{id}', [ProviderController::class, 'find']);
  });