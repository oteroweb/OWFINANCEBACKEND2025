<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RateController;

Route::group([
    'middleware' => ['auth:sanctum'],
    'prefix'     => 'rates',
], function () {
    // Rates ROUTES
    Route::post('/', [RateController::class, 'store']);
    Route::get('/{id}', [RateController::class, 'show']);
    Route::put('/{id}', [RateController::class, 'update']);
    Route::get('/', [RateController::class, 'index']);
    Route::delete('/{id}', [RateController::class, 'destroy']);
});
