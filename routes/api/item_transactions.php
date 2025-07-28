<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemTransactionController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'item_transactions',
], function () {
    //ItemTransaction ROUTES
    Route::post('/', [ItemTransactionController::class, 'save']);
    Route::get('/{id}', [ItemTransactionController::class, 'find']);
    Route::put('/{id}', [ItemTransactionController::class, 'update']);
    Route::get('/', [ItemTransactionController::class, 'all']);
    Route::patch('/{id}/status', [ItemTransactionController::class, 'change_status']);
    Route::get('/active', [ItemTransactionController::class, 'allActive']);
    Route::delete('/{id}', [ItemTransactionController::class, 'delete']);
    Route::get('/all', [ItemTransactionController::class, 'withTrashed']);
});
