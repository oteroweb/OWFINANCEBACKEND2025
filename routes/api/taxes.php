<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaxController;

// OWF-353: lectura abierta a cualquier autenticado — el selector de impuesto por fila
// de "Pago múltiple"/"Detalle-factura" necesita listar el catálogo (IGTF, Comisión
// Pago Móvil, IVA, etc.) para cualquier usuario, no solo admin. Antes TODO el grupo
// (lectura + escritura) estaba detrás de CheckRole:admin — mismo patrón de bug ya
// encontrado y corregido antes para /providers (OWF-264) y /transaction_types (OWF-303).
Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'taxes',
], function () {
    Route::get('/', [TaxController::class, 'all']);
    Route::get('/active', [TaxController::class, 'allActive']);
    Route::get('/{id}', [TaxController::class, 'find']);
});

// Escritura/gestión: solo admin.
Route::group([
    'middleware' => ['api', 'auth:sanctum', 'App\\Http\\Middleware\\CheckRole:admin'],
    'prefix'     => 'taxes',
], function () {
    Route::post('/', [TaxController::class, 'save']);
    Route::put('/{id}', [TaxController::class, 'update']);
    Route::patch('/{id}/status', [TaxController::class, 'change_status']);
    Route::delete('/{id}', [TaxController::class, 'delete']);
    Route::get('/all', [TaxController::class, 'withTrashed']);
});
