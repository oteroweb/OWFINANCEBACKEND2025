<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentTransactionController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'payment-transactions',
], function () {
    Route::post('/', [PaymentTransactionController::class, 'save']);
    Route::get('/active', [PaymentTransactionController::class, 'allActive']);
    Route::get('/all', [PaymentTransactionController::class, 'withTrashed']);
    Route::get('/', [PaymentTransactionController::class, 'all']);
    Route::patch('/{id}/status', [PaymentTransactionController::class, 'change_status']);
    Route::put('/{id}', [PaymentTransactionController::class, 'update']);
    Route::delete('/{id}', [PaymentTransactionController::class, 'delete']);
    Route::get('/{id}', [PaymentTransactionController::class, 'find']);
});
