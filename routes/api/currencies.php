<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\CurrencyController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'currencies',
], function () {
  //Currency ROUTES 
    Route::get('/', [CurrencyController::class, 'all']);
    Route::get('/active', [CurrencyController::class, 'allActive']);
    Route::get('/{id}', [CurrencyController::class, 'find']);
    Route::post('/', [CurrencyController::class, 'save']);
    Route::put('/{id}', [CurrencyController::class, 'update']);
    Route::patch('/{id}/status', [CurrencyController::class, 'change_status']);
    Route::delete('/{id}', [CurrencyController::class, 'delete']);
  });