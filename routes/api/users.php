<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::group([
    'prefix' => 'users',
], function () {
    Route::post('/', [UserController::class, 'save']);
    Route::get('/active', [UserController::class, 'allActive']);
    Route::get('/all', [UserController::class, 'withTrashed']);
    Route::get('/', [UserController::class, 'all']);
    Route::patch('/{id}/status', [UserController::class, 'change_status']);
    Route::put('/{id}', [UserController::class, 'update']);
    Route::delete('/{id}', [UserController::class, 'delete']);
    Route::get('/{id}', [UserController::class, 'find']);
});
