<?php

  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\TransactionController;

  Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'transactions',
], function () {
  //Transaction ROUTES
    Route::post('/', [TransactionController::class, 'save']);
    Route::get('/active', [TransactionController::class, 'allActive']);
    Route::get('/all', [TransactionController::class, 'withTrashed']);
    Route::get('/', [TransactionController::class, 'all']);
    Route::patch('/{id}/status', [TransactionController::class, 'change_status']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::delete('/{id}', [TransactionController::class, 'delete']);
    Route::get('/{id}', [TransactionController::class, 'find']);
  });
