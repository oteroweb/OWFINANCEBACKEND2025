<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentTransactionTaxController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'payment-transaction-taxes',
], function () {
    Route::post('/', [PaymentTransactionTaxController::class, 'save']);
    Route::get('/active', [PaymentTransactionTaxController::class, 'allActive']);
    Route::get('/all', [PaymentTransactionTaxController::class, 'withTrashed']);
    Route::get('/', [PaymentTransactionTaxController::class, 'all']);
    Route::patch('/{id}/status', [PaymentTransactionTaxController::class, 'change_status']);
    Route::put('/{id}', [PaymentTransactionTaxController::class, 'update']);
    Route::delete('/{id}', [PaymentTransactionTaxController::class, 'delete']);
    Route::get('/{id}', [PaymentTransactionTaxController::class, 'find']);
});
