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
    Route::patch('/{id}/status', [UserController::class, 'change_status'])->whereNumber('id');
    Route::put('/{id}', [UserController::class, 'update'])->whereNumber('id');
    Route::delete('/{id}', [UserController::class, 'delete'])->whereNumber('id');
    Route::get('/{id}', [UserController::class, 'find'])->whereNumber('id');

    // User-scoped jar utilities (protected)
    Route::group(['middleware' => ['auth:sanctum']], function () {
        // Perfil del usuario autenticado y actualización
    Route::get('/profile', [UserController::class, 'profile']); // includes accounts + rates
        Route::match(['put', 'patch'], '/profile', [UserController::class, 'updateProfile']);
        // Si es admin, puede actualizar el perfil de cualquier usuario por ID
        Route::match(['put', 'patch'], '/profile/{id}', [UserController::class, 'updateProfile'])->whereNumber('id');
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
