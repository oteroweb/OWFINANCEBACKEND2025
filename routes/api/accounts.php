<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AccountController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
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
    // Recalculate account balance from initial + signed sums
    Route::post('/{id}/recalculate-account', [AccountController::class, 'recalcBalanceFromInitialByType']);
    // Adjust account balance (new logic)
    Route::post('/{id}/adjust-balance', [AccountController::class, 'adjustBalance']);
    Route::put('/{id}', [AccountController::class, 'update']);
    Route::delete('/{id}', [AccountController::class, 'delete']);
    Route::get('/{id}', [AccountController::class, 'find']);
});

// UserCurrency routes (rates per user and currency)
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'user-currencies',
], function () {
    Route::get('/', [\App\Http\Controllers\Api\UserCurrencyController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\UserCurrencyController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\UserCurrencyController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\UserCurrencyController::class, 'destroy']);
});

// Backward-compat alias with underscore
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'user_currencies',
], function () {
    Route::get('/', [\App\Http\Controllers\Api\UserCurrencyController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\UserCurrencyController::class, 'store']);
    Route::put('/{id}', [\App\Http\Controllers\Api\UserCurrencyController::class, 'update']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\UserCurrencyController::class, 'destroy']);
});
