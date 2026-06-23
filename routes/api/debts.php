<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DebtController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'debts',
], function () {
    Route::get('/', [DebtController::class, 'index']);
    Route::post('/', [DebtController::class, 'store']);
    Route::get('/{id}', [DebtController::class, 'show']);
    Route::put('/{id}', [DebtController::class, 'update']);
    Route::patch('/{id}', [DebtController::class, 'update']);
    Route::post('/{id}/pay', [DebtController::class, 'payInstallment']);
    Route::delete('/{id}', [DebtController::class, 'destroy']);
});
