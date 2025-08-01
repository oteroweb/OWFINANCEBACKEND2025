<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemTransactionController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'item_transactions',
], function () {
    //ItemTransaction ROUTES
    Route::post('/', [ItemTransactionController::class, 'save']);
    Route::get('/active', [ItemTransactionController::class, 'allActive']);
    Route::get('/all', [ItemTransactionController::class, 'withTrashed']);
    Route::get('/', [ItemTransactionController::class, 'all']);
    Route::patch('/{id}/status', [ItemTransactionController::class, 'change_status']);
    Route::put('/{id}', [ItemTransactionController::class, 'update']);
    Route::delete('/{id}', [ItemTransactionController::class, 'delete']);
    Route::get('/{id}', [ItemTransactionController::class, 'find']);
});
