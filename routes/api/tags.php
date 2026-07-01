<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TagController;

Route::group([
    'middleware' => ['api', 'auth:sanctum'],
    'prefix'     => 'tags',
], function () {
    Route::get('/', [TagController::class, 'index']);
    Route::post('/', [TagController::class, 'save']);
    Route::delete('/{id}', [TagController::class, 'delete']);
});
