<?php

  use Illuminate\Support\Facades\Route;
  use App\Http\Controllers\Api\TransactionController;

  Route::group([
    'middleware' => ['api'],
    'prefix'     => 'transactions',
], function () {
  //Transaction ROUTES
    Route::post('/', [TransactionController::class, 'save']);
    Route::get('/{id}', [TransactionController::class, 'find']);
    Route::put('/{id}', [TransactionController::class, 'update']);
    Route::get('/', [TransactionController::class, 'all']);
    Route::patch('/{id}/status', [TransactionController::class, 'change_status']);
    Route::get('/active', [TransactionController::class, 'allActive']);
    Route::delete('/{id}', [TransactionController::class, 'delete']);
    Route::get('/all', [TransactionController::class, 'withTrashed']);
  });
