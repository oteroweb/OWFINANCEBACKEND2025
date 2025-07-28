<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'categories',
], function () {
    Route::post('/', [CategoryController::class, 'save']);
    Route::get('/{id}', [CategoryController::class, 'find']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::get('/', [CategoryController::class, 'all']);
    Route::patch('/{id}/status', [CategoryController::class, 'change_status']);
    Route::get('/active', [CategoryController::class, 'allActive']);
    Route::delete('/{id}', [CategoryController::class, 'delete']);
    Route::get('/all', [CategoryController::class, 'withTrashed']);
});
