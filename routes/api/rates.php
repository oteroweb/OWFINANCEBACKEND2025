<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RateController;

Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix'     => 'rates',
], function () {
    // Rates ROUTES
    Route::post('/', [RateController::class, 'save']);
    Route::get('/all', [RateController::class, 'withTrashed']);
    Route::get('/active', [RateController::class, 'allActive']);
    Route::get('/{id}', [RateController::class, 'find']);
    Route::put('/{id}', [RateController::class, 'update']);
    Route::get('/', [RateController::class, 'all']);
    Route::patch('/{id}/status', [RateController::class, 'change_status']);
    Route::delete('/{id}', [RateController::class, 'delete']);
});
