<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BusinessController;

// OWF-370: Fase 2 de "Grupo Familiar y Contabilidad Empresarial" — empresas y contabilidad segmentada.
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'businesses',
], function () {
    Route::get('/', [BusinessController::class, 'all']);
    Route::post('/', [BusinessController::class, 'save']);
    Route::get('/{id}', [BusinessController::class, 'find']);
    Route::put('/{id}', [BusinessController::class, 'update']);
    Route::delete('/{id}', [BusinessController::class, 'delete']);
    Route::post('/{id}/onboarding', [BusinessController::class, 'onboarding']);
    Route::post('/{id}/invite', [BusinessController::class, 'invite']);
    Route::post('/{id}/accept', [BusinessController::class, 'accept']);
    Route::post('/{id}/decline', [BusinessController::class, 'decline']);
    Route::patch('/{id}/users/{userId}', [BusinessController::class, 'updateRole']);
    Route::delete('/{id}/users/{userId}', [BusinessController::class, 'removeUser']);
});
