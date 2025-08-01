<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemTaxController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'item-taxes',
], function () {
    Route::post('/', [ItemTaxController::class, 'save']);
    Route::get('/active', [ItemTaxController::class, 'allActive']);
    Route::get('/all', [ItemTaxController::class, 'withTrashed']);
    Route::get('/', [ItemTaxController::class, 'all']);
    Route::patch('/{id}/status', [ItemTaxController::class, 'change_status']);
    Route::put('/{id}', [ItemTaxController::class, 'update']);
    Route::delete('/{id}', [ItemTaxController::class, 'delete']);
    Route::get('/{id}', [ItemTaxController::class, 'find']);
});
