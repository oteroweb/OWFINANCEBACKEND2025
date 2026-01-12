<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JarController;
use App\Http\Controllers\Api\JarIncomeController;
use App\Http\Controllers\Api\JarAdjustmentController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'jars',
], function () {
    //Jar ROUTES
    Route::post('/', [JarController::class, 'save']);
    Route::get('/active', [JarController::class, 'allActive']);
    Route::get('/all', [JarController::class, 'withTrashed']);
    Route::get('/', [JarController::class, 'all']);

    // Income summary endpoint
    Route::get('/income-summary', [JarIncomeController::class, 'getIncomeSummary']);

    Route::get('/{id}', [JarController::class, 'find']);
    Route::put('/{id}', [JarController::class, 'update']);
    Route::patch('/{id}/status', [JarController::class, 'change_status']);
    Route::post('/{id}/categories', [JarController::class, 'setCategories']);
    Route::post('/{id}/base-categories', [JarController::class, 'setBaseCategories']);

    // Adjustment endpoints
    Route::post('/{id}/adjust', [JarAdjustmentController::class, 'adjust']);
    Route::post('/{id}/adjust/reset', [JarAdjustmentController::class, 'resetAdjustment']);

    Route::delete('/{id}', [JarController::class, 'delete']);
});

