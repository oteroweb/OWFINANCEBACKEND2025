<?php
    
  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\CurrencyController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'currencies',
], function () {
  //Currency ROUTES 
    Route::post('/', [CurrencyController::class, 'save']);
    Route::get('/{id}', [CurrencyController::class, 'find']);
    Route::put('/{id}', [CurrencyController::class, 'update']);
    Route::get('/', [CurrencyController::class, 'all']);
    Route::patch('/{id}/status', [CurrencyController::class, 'change_status']);
    Route::get('/active', [CurrencyController::class, 'allActive']);
    Route::delete('/{id}', [CurrencyController::class, 'delete']);
    Route::get('/all', [CurrencyController::class, 'withTrashed']);
  });