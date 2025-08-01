<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountTaxController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'accounts-taxes',
], function () {
    Route::post('/', [AccountTaxController::class, 'save']);
    Route::get('/active', [AccountTaxController::class, 'allActive']);
    Route::get('/all', [AccountTaxController::class, 'withTrashed']);
    Route::get('/', [AccountTaxController::class, 'all']);
    Route::patch('/{id}/status', [AccountTaxController::class, 'change_status']);
    Route::put('/{id}', [AccountTaxController::class, 'update']);
    Route::delete('/{id}', [AccountTaxController::class, 'delete']);
    Route::get('/{id}', [AccountTaxController::class, 'find']);
});
