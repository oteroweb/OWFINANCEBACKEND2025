<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JarController;
use App\Http\Controllers\Api\JarIncomeController;
use App\Http\Controllers\Api\JarBalanceController;
use App\Http\Controllers\Api\JarSettingController;
use App\Http\Controllers\Api\JarWithdrawalController;
use App\Http\Controllers\Api\JarTransferController;
use App\Http\Controllers\Api\JarSavingsController;

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
    Route::get('/settings/monthly', [JarSettingController::class, 'showMonthly']);
    Route::put('/settings/monthly', [JarSettingController::class, 'updateMonthly']);

    // Theoretical savings summary
    Route::get('/theoretical-savings', [JarSavingsController::class, 'getSummary']);

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
    Route::delete('/{id}/adjustments', [JarBalanceController::class, 'clearAdjustments']);
    Route::post('/{id}/reset-adjustment', [JarBalanceController::class, 'resetAdjustmentForNextPeriod']);
    Route::post('/{id}/leverage', [JarBalanceController::class, 'leverage']);

    // Withdrawals (usage)
    Route::get('/{id}/withdrawals', [JarWithdrawalController::class, 'index']);
    Route::post('/{id}/withdraw', [JarWithdrawalController::class, 'store']);

    // Transfers between jars
    Route::get('/{id}/transfers', [JarTransferController::class, 'index']);
    Route::post('/{id}/transfer', [JarTransferController::class, 'store']);

    Route::delete('/{id}', [JarController::class, 'delete']);
});

