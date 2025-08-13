<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionTypeController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'transaction_types',
], function () {
    Route::post('/', [TransactionTypeController::class, 'save']);
    Route::get('/active', [TransactionTypeController::class, 'allActive']);
    Route::get('/all', [TransactionTypeController::class, 'withTrashed']);
    Route::get('/', [TransactionTypeController::class, 'all']);
    Route::patch('/{id}/status', [TransactionTypeController::class, 'change_status']);
    Route::put('/{id}', [TransactionTypeController::class, 'update']);
    Route::delete('/{id}', [TransactionTypeController::class, 'delete']);
    Route::get('/{id}', [TransactionTypeController::class, 'find']);
});
