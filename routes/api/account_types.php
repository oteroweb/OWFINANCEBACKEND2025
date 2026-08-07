<?php

  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\AccountTypeController;

  // OWF-367: lectura abierta a cualquier autenticado — crear una cuenta (Configuración,
  // y el nuevo paso de "cuenta inicial" del onboarding) necesita listar el catálogo de
  // tipos de cuenta para cualquier usuario, no solo admin. Antes TODO el grupo (lectura +
  // escritura) estaba detrás de CheckRole:admin — mismo patrón de bug ya encontrado y
  // corregido antes para /providers (OWF-264), /transaction_types (OWF-303) y /taxes (OWF-353).
  //
  // Orden de registro importa: Laravel matchea por orden de registro, no por
  // especificidad de segmento — GET /all (literal, admin) debe registrarse ANTES que
  // GET /{id} (dinámico, público) o "/account_types/all" caería en find('all') en vez
  // de withTrashed(). Confirmado en vivo que este bug ya existe hoy en /taxes/all
  // (mismo patrón, orden invertido) — no se toca acá (fuera de alcance de OWF-367),
  // pero este archivo nuevo lo evita desde el principio.
  Route::group([
    'middleware' => ['api', 'auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'],
    'prefix'     => 'account_types',
], function () {
    Route::post('/', [AccountTypeController::class, 'save']);
    Route::get('/all', [AccountTypeController::class, 'withTrashed']);
    Route::patch('/{id}/status', [AccountTypeController::class, 'change_status']);
    Route::put('/{id}', [AccountTypeController::class, 'update']);
    Route::delete('/{id}', [AccountTypeController::class, 'delete']);
  });

  // Lectura: cualquier autenticado.
  Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'account_types',
], function () {
    Route::get('/active', [AccountTypeController::class, 'allActive']);
    Route::get('/', [AccountTypeController::class, 'all']);
    Route::get('/{id}', [AccountTypeController::class, 'find']);
  });
