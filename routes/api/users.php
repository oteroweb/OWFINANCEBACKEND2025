<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\UserJarController;

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

    // User-scoped jar utilities (protected)
    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('/{userId}/jars', [UserJarController::class, 'listJars']);
        Route::get('/{userId}/jars/summary', [UserJarController::class, 'summary']);
        Route::get('/{userId}/jars/{jarId}/items', [UserJarController::class, 'jarItems']);
    Route::post('/{userId}/jars/save', [UserJarController::class, 'saveJar']);
    Route::put('/{userId}/jars/bulk', [UserJarController::class, 'bulkUpsertJars']);
        Route::post('/{userId}/jars', [UserJarController::class, 'createJar']);
        Route::put('/{userId}/jars/{id}', [UserJarController::class, 'updateJar']);
        Route::delete('/{userId}/jars/{id}', [UserJarController::class, 'deleteJar']);
        Route::put('/{userId}/jars/{id}/categories', [UserJarController::class, 'replaceJarCategories']);
        Route::post('/{userId}/jars/{id}/categories', [UserJarController::class, 'replaceJarCategories']);
        Route::get('/{userId}/categories/unassigned', [UserJarController::class, 'unassignedCategories']);
        Route::patch('/{userId}/item-transactions/{id}', [UserJarController::class, 'updateItemTransactionJar']);
    });
});
