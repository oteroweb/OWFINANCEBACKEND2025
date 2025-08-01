<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'categories',
], function () {
    Route::post('/', [CategoryController::class, 'save']);
    Route::get('/active', [CategoryController::class, 'allActive']);
    Route::get('/all', [CategoryController::class, 'withTrashed']);
    Route::get('/', [CategoryController::class, 'all']);
    Route::patch('/{id}/status', [CategoryController::class, 'change_status']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'delete']);
    Route::get('/{id}', [CategoryController::class, 'find']);
});
