<?php

  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\CurrencyController;

  // Public read-only endpoints (catalog data, no auth required)
  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'currencies',
], function () {
    Route::get('/', [CurrencyController::class, 'all']);
    Route::get('/active', [CurrencyController::class, 'allActive']);
});

  // Admin write operations
  Route::group([
    'middleware' => ['api', 'auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'],
    'prefix'     => 'currencies',
], function () {
    Route::post('/', [CurrencyController::class, 'save']);
    Route::get('/all', [CurrencyController::class, 'withTrashed']);
    Route::get('/{id}', [CurrencyController::class, 'find']);
    Route::put('/{id}', [CurrencyController::class, 'update']);
    Route::patch('/{id}/status', [CurrencyController::class, 'change_status']);
    Route::delete('/{id}', [CurrencyController::class, 'delete']);
});
