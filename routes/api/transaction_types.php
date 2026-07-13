<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TransactionTypeController;

// OWF-303: /transaction_types era admin-only entero, pero el catálogo (activos/todos/find)
// lo necesita CUALQUIER usuario autenticado para armar el payload de una transacción
// (mapeo tipo→transaction_type_id en el formulario). Mismo patrón que OWF-264 (providers):
// lectura para cualquier usuario, gestión (crear/editar/borrar/withTrashed) admin-only.
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'transaction_types',
], function () {
    Route::get('/active', [TransactionTypeController::class, 'allActive']);
    Route::get('/', [TransactionTypeController::class, 'all']);
    Route::get('/{id}', [TransactionTypeController::class, 'find']);
});

Route::group([
    'middleware' => ['api', 'auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'],
    'prefix'     => 'transaction_types',
], function () {
    Route::post('/', [TransactionTypeController::class, 'save']);
    Route::get('/all', [TransactionTypeController::class, 'withTrashed']);
    Route::patch('/{id}/status', [TransactionTypeController::class, 'change_status']);
    Route::put('/{id}', [TransactionTypeController::class, 'update']);
    Route::delete('/{id}', [TransactionTypeController::class, 'delete']);
});
