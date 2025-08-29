<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'accounts',
], function () {
    //Account ROUTES
    Route::post('/', [AccountController::class, 'save']);
    Route::get('/active', [AccountController::class, 'allActive']);
    Route::get('/all', [AccountController::class, 'withTrashed']);
    Route::get('/', [AccountController::class, 'all']);
    // Account folders
    Route::get('/folders', [\App\Http\Controllers\Api\AccountFolderController::class, 'index']);
    Route::post('/folders', [\App\Http\Controllers\Api\AccountFolderController::class, 'store']);
    Route::put('/folders/{id}', [\App\Http\Controllers\Api\AccountFolderController::class, 'rename']);
    Route::delete('/folders/{id}', [\App\Http\Controllers\Api\AccountFolderController::class, 'destroy']);
    Route::patch('/folders/{id}/move', [\App\Http\Controllers\Api\AccountFolderController::class, 'move']);
    // Move account
    Route::patch('/{id}/move', [AccountController::class, 'move']);
    // Account tree
    Route::get('/tree', [AccountController::class, 'tree']);
    Route::get('/folders/tree', [\App\Http\Controllers\Api\AccountFolderController::class, 'tree']);
    Route::patch('/{id}/status', [AccountController::class, 'change_status']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'delete']);
    Route::get('/{id}', [AccountController::class, 'find']);
});
