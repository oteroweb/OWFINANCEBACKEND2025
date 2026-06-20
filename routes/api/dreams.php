<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DreamController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'dreams',
], function () {
    Route::get('/', [DreamController::class, 'index']);
    Route::post('/', [DreamController::class, 'store']);
    Route::get('/{id}', [DreamController::class, 'show']);
    Route::put('/{id}', [DreamController::class, 'update']);
    Route::patch('/{id}', [DreamController::class, 'update']);
    Route::post('/{id}/deposit', [DreamController::class, 'deposit']);
    Route::delete('/{id}', [DreamController::class, 'destroy']);
});
