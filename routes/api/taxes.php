<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TaxController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'taxes',
], function () {
    //Tax ROUTES
    Route::post('/', [TaxController::class, 'save']);
    Route::get('/{id}', [TaxController::class, 'find']);
    Route::put('/{id}', [TaxController::class, 'update']);
    Route::get('/', [TaxController::class, 'all']);
    Route::patch('/{id}/status', [TaxController::class, 'change_status']);
    Route::get('/active', [TaxController::class, 'allActive']);
    Route::delete('/{id}', [TaxController::class, 'delete']);
    Route::get('/all', [TaxController::class, 'withTrashed']);
});
