<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ItemController;

Route::group([
    'middleware' => ['api'],
    'prefix'     => 'items',
], function () {
    Route::post('/', [ItemController::class, 'save']);
    Route::get('/active', [ItemController::class, 'allActive']);
    Route::get('/all', [ItemController::class, 'withTrashed']);
    Route::get('/', [ItemController::class, 'all']);
    Route::patch('/{id}/status', [ItemController::class, 'change_status']);
    Route::put('/{id}', [ItemController::class, 'update']);
    Route::delete('/{id}', [ItemController::class, 'delete']);
    Route::get('/{id}', [ItemController::class, 'find']);
});
