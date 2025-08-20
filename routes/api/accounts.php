<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::group([
    'middleware' => ['api','auth:sanctum'],
    'prefix'     => 'accounts',
], function () {
    //Account ROUTES
    Route::post('/', [AccountController::class, 'save']);
    Route::get('/active', [AccountController::class, 'allActive']);
    Route::get('/all', [AccountController::class, 'withTrashed']);
    Route::get('/', [AccountController::class, 'all']);
    // Account folders
    Route::post('/folders', [\App\Http\Controllers\Api\AccountFolderController::class, 'store']);
    // Move account
    Route::patch('/{id}/move', [AccountController::class, 'move']);
    // Account tree
    Route::get('/tree', [AccountController::class, 'tree']);
    Route::patch('/{id}/status', [AccountController::class, 'change_status']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'delete']);
    Route::get('/{id}', [AccountController::class, 'find']);
});
