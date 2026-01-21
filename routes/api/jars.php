<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JarController;
use App\Http\Controllers\Api\JarIncomeController;
use App\Http\Controllers\Api\JarAdjustmentController;
use App\Http\Controllers\Api\JarBalanceController;
use App\Http\Controllers\Api\JarSettingController;
use App\Http\Controllers\Api\JarWithdrawalController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'jars',
], function () {
    //Jar ROUTES
    Route::post('/bulk-sync', [JarController::class, 'bulkSync']); // Sincronización masiva
    Route::post('/', [JarController::class, 'save']);
    Route::get('/active', [JarController::class, 'allActive']);
    Route::get('/all', [JarController::class, 'withTrashed']);
    Route::get('/', [JarController::class, 'all']);

    // Jar settings (global)
    Route::get('/settings', [JarSettingController::class, 'show']);
    Route::put('/settings', [JarSettingController::class, 'update']);

    // Income summary endpoint
    Route::get('/income-summary', [JarIncomeController::class, 'getIncomeSummary']);

    Route::get('/{id}', [JarController::class, 'find']);
    Route::put('/{id}', [JarController::class, 'update']);
    Route::patch('/{id}/status', [JarController::class, 'change_status']);
    Route::post('/{id}/categories', [JarController::class, 'setCategories']);
    Route::post('/{id}/base-categories', [JarController::class, 'setBaseCategories']);

    // Balance and Adjustment endpoints
    Route::get('/{id}/balance', [JarBalanceController::class, 'getBalance']);
    Route::get('/{id}/adjustments', [JarBalanceController::class, 'getAdjustmentHistory']);
    Route::post('/{id}/adjust', [JarBalanceController::class, 'adjustBalance']);
    Route::post('/{id}/reset-adjustment', [JarBalanceController::class, 'resetAdjustmentForNextPeriod']);

    // Withdrawals (usage)
    Route::get('/{id}/withdrawals', [JarWithdrawalController::class, 'index']);
    Route::post('/{id}/withdraw', [JarWithdrawalController::class, 'store']);

    Route::delete('/{id}', [JarController::class, 'delete']);
});

