<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FamilyGroupController;

// OWF-369: Fase 1 de "Grupo Familiar y Contabilidad Empresarial" — grupos familiares
// para poder compartir cuentas entre usuarios (ver AccountController::share/unshare).
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'family-groups',
], function () {
    Route::get('/', [FamilyGroupController::class, 'all']);
    Route::post('/', [FamilyGroupController::class, 'save']);
    Route::get('/{id}', [FamilyGroupController::class, 'find']);
    Route::post('/{id}/invite', [FamilyGroupController::class, 'invite']);
    Route::post('/{id}/accept', [FamilyGroupController::class, 'accept']);
    Route::post('/{id}/decline', [FamilyGroupController::class, 'decline']);
    Route::delete('/{id}/members/{userId}', [FamilyGroupController::class, 'removeMember']);
});
