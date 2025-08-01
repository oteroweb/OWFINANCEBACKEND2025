<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'clients',
], function () {
    Route::post('/', [ClientController::class, 'save']);
    Route::get('/active', [ClientController::class, 'allActive']);
    Route::get('/all', [ClientController::class, 'withTrashed']);
    Route::get('/', [ClientController::class, 'all']);
    Route::patch('/{id}/status', [ClientController::class, 'change_status']);
    Route::put('/{id}', [ClientController::class, 'update']);
    Route::delete('/{id}', [ClientController::class, 'delete']);
    Route::get('/{id}', [ClientController::class, 'find']);
});
