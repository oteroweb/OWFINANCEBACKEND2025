<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JarController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'jars',
], function () {
    //Jar ROUTES
    Route::post('/', [JarController::class, 'save']);
    Route::get('/{id}', [JarController::class, 'find']);
    Route::put('/{id}', [JarController::class, 'update']);
    Route::get('/', [JarController::class, 'all']);
    Route::patch('/{id}/status', [JarController::class, 'change_status']);
    Route::get('/active', [JarController::class, 'allActive']);
    Route::delete('/{id}', [JarController::class, 'delete']);
    Route::get('/all', [JarController::class, 'withTrashed']);
});
